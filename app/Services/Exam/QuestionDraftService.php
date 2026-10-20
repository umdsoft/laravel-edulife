<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamFormat;
use App\Enums\Exam\QuestionOrigin;
use App\Enums\Exam\ReviewStatus;
use App\Jobs\Exam\GenerateQuestionDraftsJob;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Support\Difficulty;
use App\Models\User;
use App\Support\Exam\QuestionText;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stage 4 — AI question drafts through the Anthropic Messages API.
 *
 * Laravel's Http client is used instead of an SDK: the project has no
 * Anthropic SDK, one POST /v1/messages is all that is needed, and
 * Http::fake() makes every path testable without a network call.
 *
 * Guarantees:
 *  - nothing is sent while exam.ai.api_key is empty (run = skipped, log line);
 *  - the AI never marks the correct answer: options are stored with
 *    is_correct = NULL and the question enters the teacher's review queue
 *    (origin ai_draft, review_status in_review via QuestionReviewService);
 *  - the reply is validated item by item (QuestionDraftSchema) before
 *    anything is written; tokens and the estimated cost are logged per run.
 */
class QuestionDraftService
{
    public function __construct(private readonly QuestionReviewService $review) {}

    public function isConfigured(): bool
    {
        return trim((string) config('exam.ai.api_key')) !== '';
    }

    /**
     * Create a run and queue it. $params: subject_id, topic_id?, difficulty_level,
     * exam_format?, count, source_document_ids? (already validated by the caller).
     */
    public function start(array $params, ?User $by = null, string $trigger = QuestionDraftRun::TRIGGER_MANUAL): QuestionDraftRun
    {
        $run = QuestionDraftRun::create([
            'subject_id' => $params['subject_id'],
            'topic_id' => $params['topic_id'] ?? null,
            'difficulty_level' => (int) $params['difficulty_level'],
            'exam_format' => $params['exam_format'] ?? null,
            'question_type' => 'single_choice',
            'count_requested' => min((int) $params['count'], (int) config('exam.ai.max_count_per_run', 20)),
            'source_document_ids' => array_values($params['source_document_ids'] ?? []) ?: null,
            'trigger' => $trigger,
            'status' => QuestionDraftRun::STATUS_QUEUED,
            'model' => (string) config('exam.ai.model'),
            'requested_by' => $by?->id,
        ]);

        if (! $this->isConfigured()) {
            $this->skip($run, 'ANTHROPIC_API_KEY is not set');

            return $run;
        }

        GenerateQuestionDraftsJob::dispatch($run->id);

        return $run;
    }

    /** Run the API call and store the drafts (called by the queue job). */
    public function execute(QuestionDraftRun $run): QuestionDraftRun
    {
        if ($run->isFinished() || $run->status === QuestionDraftRun::STATUS_RUNNING) {
            return $run;
        }
        if (! $this->isConfigured()) {
            $this->skip($run, 'ANTHROPIC_API_KEY is not set');

            return $run;
        }

        if ($run->trigger === QuestionDraftRun::TRIGGER_SCHEDULE && $this->weeklyBudgetReached()) {
            $this->skip($run, 'weekly AI budget reached');

            return $run;
        }

        $subject = Subject::query()->find($run->subject_id);
        $topic = $run->topic_id ? Topic::query()->find($run->topic_id) : null;
        if ($subject === null) {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'subject not found']);

            return $run;
        }

        $documents = $this->sourceDocuments($run, $subject, $topic);
        if ($documents->isEmpty()) {
            $this->skip($run, 'no active source documents for this subject/topic');

            return $run;
        }

        $prompt = QuestionDraftPrompt::load((string) config('exam.ai.prompt', 'question_draft'));
        $allowedCodes = $this->allowedTopicCodes($subject, $topic);
        if ($allowedCodes === []) {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'the subject has no topics']);

            return $run;
        }

        [$sourcesText, $excerpts] = $this->buildExcerpts($documents, $run);
        $userMessage = $prompt->renderUser([
            'subject_name' => $subject->name_uz,
            'subject_code' => $subject->code,
            'topic_name' => $topic?->name_uz ?? $subject->name_uz.' (istalgan mavzu)',
            'topic_code' => $topic?->code ?? '-',
            'allowed_topic_codes' => implode(', ', $allowedCodes),
            'difficulty_level' => $run->difficulty_level,
            'exam_format' => $run->exam_format ? (ExamFormat::tryFrom($run->exam_format)?->label() ?? $run->exam_format) : 'Milliy sertifikat / Abituriyent',
            'count' => $run->count_requested,
            'subject_rules' => $this->subjectRules($subject),
            'sources' => $sourcesText,
        ]);

        $model = (string) config('exam.ai.model');
        $run->fill([
            'status' => QuestionDraftRun::STATUS_RUNNING,
            'started_at' => now(),
            'model' => $model,
            'prompt_name' => $prompt->name,
            'prompt_version' => $prompt->version,
            'source_document_ids' => $documents->pluck('id')->values()->all(),
            'source_excerpts' => $excerpts,
        ])->save();

        try {
            $response = $this->send($model, $prompt->system, $userMessage);
        } catch (ConnectionException $e) {
            Log::warning('exam.ai_drafts: connection failed', ['run' => $run->id]);
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'connection failed']);

            return $run;
        }

        if (! $response->successful()) {
            $type = (string) ($response->json('error.type') ?? 'unknown');
            Log::warning('exam.ai_drafts: API error', ['run' => $run->id, 'status' => $response->status(), 'type' => $type]);
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => "API HTTP {$response->status()}: {$type}"]);

            return $run;
        }

        $this->recordUsage($run, $response);
        $stopReason = (string) $response->json('stop_reason');

        if ($stopReason === 'refusal') {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'the model declined the request (refusal)']);

            return $run;
        }
        if ($stopReason === 'max_tokens') {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'reply cut off at max_tokens; lower the count or raise EXAM_AI_MAX_TOKENS']);

            return $run;
        }

        $text = collect($response->json('content') ?? [])
            ->filter(fn ($block) => is_array($block) && ($block['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode('');

        $items = QuestionDraftSchema::decode($text);
        if ($items === null) {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'reply does not match the JSON schema']);

            return $run;
        }

        $this->storeItems($run, $subject, $items, $allowedCodes);

        Log::info('exam.ai_drafts: run finished', [
            'run' => $run->id,
            'status' => $run->status,
            'created' => $run->drafts_created,
            'rejected' => $run->drafts_rejected,
            'duplicates' => $run->duplicates_skipped,
            'cost_usd' => (string) $run->estimated_cost_usd,
        ]);

        return $run;
    }

    /** Estimated cost of this week's runs (Monday 00:00, app timezone). */
    public function weeklySpend(): float
    {
        return (float) QuestionDraftRun::query()->since(now()->startOfWeek())->sum('estimated_cost_usd');
    }

    public function weeklyBudgetReached(): bool
    {
        $budget = (float) config('exam.ai.weekly.budget_usd', 0);

        return $budget > 0 && $this->weeklySpend() >= $budget;
    }

    /** Estimated USD cost of a usage block for a model (config exam.ai.pricing, per 1M tokens). */
    public static function estimateCost(?string $model, array $usage): float
    {
        $pricing = config('exam.ai.pricing', []);
        $price = $pricing[$model] ?? $pricing[config('exam.ai.model')] ?? null;
        if ($price === null) {
            return 0.0;
        }

        $cost = ((int) ($usage['input_tokens'] ?? 0)) * $price['input']
            + ((int) ($usage['output_tokens'] ?? 0)) * $price['output']
            + ((int) ($usage['cache_creation_input_tokens'] ?? 0)) * ($price['cache_write'] ?? $price['input'] * 1.25)
            + ((int) ($usage['cache_read_input_tokens'] ?? 0)) * ($price['cache_read'] ?? $price['input'] * 0.1);

        return round($cost / 1_000_000, 6);
    }

    // ------------------------------------------------------------------

    private function send(string $model, string $system, string $userMessage): Response
    {
        $body = [
            'model' => $model,
            'max_tokens' => (int) config('exam.ai.max_tokens', 16000),
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $userMessage]],
            'output_config' => [
                'effort' => (string) config('exam.ai.effort', 'high'),
                'format' => ['type' => 'json_schema', 'schema' => QuestionDraftSchema::jsonSchema()],
            ],
        ];

        $headers = [
            'x-api-key' => (string) config('exam.ai.api_key'),
            'anthropic-version' => (string) config('exam.ai.api_version', '2023-06-01'),
        ];

        $fallbacks = (string) config('exam.ai.fallbacks', '');
        if ($fallbacks !== '' && $this->supportsServerFallback($model)) {
            $body['fallbacks'] = $fallbacks;
            $headers['anthropic-beta'] = (string) config('exam.ai.fallbacks_beta');
        }

        return Http::withHeaders($headers)
            ->acceptJson()
            ->timeout((int) config('exam.ai.timeout_seconds', 300))
            ->post(rtrim((string) config('exam.ai.base_url'), '/').'/v1/messages', $body);
    }

    /** Server-side refusal fallback ("default" form) exists for these model lines. */
    private function supportsServerFallback(string $model): bool
    {
        return (bool) preg_match('/^claude-(opus-5|fable-5|sonnet-5-5)/', $model);
    }

    private function recordUsage(QuestionDraftRun $run, Response $response): void
    {
        $usage = (array) ($response->json('usage') ?? []);
        $responseModel = (string) ($response->json('model') ?? $run->model);

        $run->fill([
            'response_model' => $responseModel,
            'stop_reason' => $response->json('stop_reason'),
            'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
            'cache_creation_input_tokens' => (int) ($usage['cache_creation_input_tokens'] ?? 0),
            'cache_read_input_tokens' => (int) ($usage['cache_read_input_tokens'] ?? 0),
            'estimated_cost_usd' => self::estimateCost($responseModel, $usage),
        ])->save();
    }

    /** @param list<mixed> $items */
    private function storeItems(QuestionDraftRun $run, Subject $subject, array $items, array $allowedCodes): void
    {
        $physics = $subject->code === Subject::PHYSICS;
        $storeHint = (bool) config('exam.ai.store_suggested_answer', false);
        $topicIds = Topic::query()->where('subject_id', $subject->id)->whereIn('code', $allowedCodes)->pluck('id', 'code');

        $created = [];
        $errors = [];
        $duplicates = 0;
        $seenHashes = [];

        foreach (array_slice($items, 0, $run->count_requested) as $index => $raw) {
            [$item, $itemErrors] = QuestionDraftSchema::validateItem($raw, $allowedCodes, $physics);
            if ($item === null) {
                $errors[] = ['index' => $index, 'errors' => array_slice($itemErrors, 0, 5)];

                continue;
            }

            $hash = QuestionText::hash($item['stem']);
            if (isset($seenHashes[$hash]) || QuestionBank::withTrashed()->where('normalized_hash', $hash)->exists()) {
                $duplicates++;

                continue;
            }
            $seenHashes[$hash] = true;

            $created[] = DB::transaction(fn () => $this->storeDraft($run, $subject, $item, $topicIds[$item['topic_code']], $storeHint));
        }

        $extra = max(0, count($items) - $run->count_requested);
        $rejected = count($errors);
        $status = match (true) {
            $created === [] && $rejected > 0 => QuestionDraftRun::STATUS_FAILED,
            $rejected > 0 || $created === [] => QuestionDraftRun::STATUS_PARTIAL,
            default => QuestionDraftRun::STATUS_SUCCEEDED,
        };

        $run->markFinished($status, [
            'drafts_created' => count($created),
            'drafts_rejected' => $rejected,
            'duplicates_skipped' => $duplicates,
            'question_ids' => $created ?: null,
            'validation_errors' => $errors ?: null,
            'error' => match (true) {
                $status === QuestionDraftRun::STATUS_FAILED => 'no item passed validation',
                $extra > 0 => "{$extra} extra item(s) ignored",
                $created === [] && $duplicates > 0 => 'all items were duplicates',
                default => null,
            },
        ]);
    }

    private function storeDraft(QuestionDraftRun $run, Subject $subject, array $item, string $topicId, bool $storeHint): string
    {
        $level = $item['difficulty_level'];
        $question = QuestionBank::create([
            'subject_id' => $subject->id,
            'topic_id' => $topicId,
            'question_type' => 'single_choice',
            'question_text' => $item['stem'],
            'difficulty' => Difficulty::legacyBankValue($level),
            'difficulty_level' => $level,
            'exam_formats' => $run->exam_format ? [$run->exam_format] : null,
            'source_ref' => mb_substr($item['source_ref'], 0, 255),
            'origin' => QuestionOrigin::AiDraft,
            'review_status' => ReviewStatus::Draft,
            'content_format' => 'latex_markdown',
            'units_note' => $item['units_note'] !== null && $item['units_note'] !== '' ? mb_substr($item['units_note'], 0, 500) : null,
            'base_points' => 1,
            'estimated_time_seconds' => 60 + 30 * $level,
            'is_demo' => false,
            'status' => QuestionBank::STATUS_DRAFT,
            'created_by' => $run->requested_by,
        ]);

        foreach ($item['options'] as $i => $option) {
            QuestionBankOption::create([
                'question_id' => $question->id,
                'label' => $option['label'],
                'text' => $option['text'],
                'is_correct' => null, // the teacher decides
                'ai_suggested_correct' => $storeHint && $item['suggested_correct_label'] !== null
                    ? $option['label'] === $item['suggested_correct_label']
                    : null,
                'sort_order' => $i,
            ]);
        }

        // draft -> in_review through the review workflow (teacher's queue)
        $this->review->submitForReview($question);

        return $question->id;
    }

    /** @return Collection<int, ExamSourceDocument> */
    private function sourceDocuments(QuestionDraftRun $run, Subject $subject, ?Topic $topic): Collection
    {
        $query = ExamSourceDocument::query()->active()->where('subject_id', $subject->id);
        if (! empty($run->source_document_ids)) {
            $query->whereIn('id', $run->source_document_ids);
        } else {
            $query->usableFor($subject, $topic);
        }

        return $query->latest()->limit(8)->get();
    }

    /** @return list<string> */
    private function allowedTopicCodes(Subject $subject, ?Topic $topic): array
    {
        $query = Topic::query()->where('subject_id', $subject->id)->where('is_active', true);
        if ($topic !== null) {
            $query->whereIn('id', $topic->descendantAndSelfIds());
        }

        return $query->orderBy('level')->orderBy('sort_order')->limit(200)->pluck('code')->filter()->values()->all();
    }

    private function subjectRules(Subject $subject): string
    {
        return $subject->code === Subject::PHYSICS
            ? 'Physics rules: SI units; write every constant you use (g = 10 m/s² unless the excerpt gives 9,8 m/s²) in the stem; units_note is required.'
            : 'Math rules: exact values (fractions, roots, π) in options unless the excerpt asks for decimals; units_note may be null.';
    }

    /**
     * Quote the documents within exam.ai.max_source_chars. A long document is
     * cut to a window whose start depends on the run id, so successive runs
     * see different parts of the same manual.
     *
     * @param  Collection<int, ExamSourceDocument>  $documents
     * @return array{0: string, 1: list<array{id: string, offset: int, length: int}>}
     */
    private function buildExcerpts(Collection $documents, QuestionDraftRun $run): array
    {
        $budget = max(2000, (int) config('exam.ai.max_source_chars', 40000));
        $perDoc = max(2000, intdiv($budget, max(1, $documents->count())));

        $blocks = [];
        $meta = [];
        foreach ($documents->values() as $i => $doc) {
            $text = str_replace("\r\n", "\n", (string) $doc->content_text);
            $length = mb_strlen($text, 'UTF-8');
            $offset = 0;
            if ($length > $perDoc) {
                $offset = crc32($run->id.$doc->id) % ($length - $perDoc);
                // start at a paragraph boundary when one is close
                $before = mb_substr($text, max(0, $offset - 500), min(500, $offset), 'UTF-8');
                $pos = mb_strrpos($before, "\n\n", 0, 'UTF-8');
                if ($pos !== false) {
                    $offset = max(0, $offset - mb_strlen($before, 'UTF-8') + $pos + 2);
                }
                $text = mb_substr($text, $offset, $perDoc, 'UTF-8');
            }

            $tag = 'S'.($i + 1);
            $label = trim($doc->title.($doc->source_ref ? ' — '.$doc->source_ref : ''));
            $blocks[] = "[{$tag}] {$label}\n<excerpt>\n".trim($text)."\n</excerpt>";
            $meta[] = ['id' => $doc->id, 'tag' => $tag, 'offset' => $offset, 'length' => mb_strlen($text, 'UTF-8')];
        }

        return [implode("\n\n", $blocks), $meta];
    }

    private function skip(QuestionDraftRun $run, string $reason): void
    {
        Log::info('exam.ai_drafts: run skipped', ['run' => $run->id, 'reason' => $reason]);
        $run->markFinished(QuestionDraftRun::STATUS_SKIPPED, ['error' => $reason]);
    }
}
