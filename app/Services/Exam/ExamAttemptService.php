<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Exceptions\Exam\ExamUnavailable;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\Grading\NumberParser;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Exam engine for one attempt (olympiad pattern: server side deadline,
 * session token, heartbeat, violation counter):
 *
 *  start()        one attempt per student per session; variant generated on demand
 *                 when it was not pre-generated; expires_at = start + duration,
 *                 capped by the session end
 *  saveAnswers()  batched autosave; rejects answers after the deadline (+grace)
 *  submit()       manual or automatic (deadline) submission, then grading
 *  logEvent()     light anti-cheat log (tab switches, ...) - counted, never blocking
 */
class ExamAttemptService
{
    public function __construct(
        private VariantGenerator $generator,
        private GradingService $grading,
        private ExamPlacementService $placement,
    ) {}

    public function start(ExamSession $session, User $user, array $client = [], ?CarbonInterface $now = null): ExamAttempt
    {
        $now ??= now();

        $existing = ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return $this->resume($existing, $now);
        }

        if ($session->restricted_to_user_id !== null && $session->restricted_to_user_id !== $user->id) {
            throw ExamUnavailable::notAllowed();
        }

        if (! $session->isOpenAt($now)) {
            $notYet = in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled], true)
                || ($session->starts_at !== null && $session->starts_at->gt($now));
            throw $notYet ? ExamUnavailable::notOpen() : ExamUnavailable::closed();
        }

        if ($session->type === ExamSessionType::Placement) {
            $this->placement->assertCanStart($session, $user);
        } elseif ($session->restricted_to_user_id === null) {
            $code = $session->subject()->value('code');
            $profile = StudentExamProfile::query()->where('user_id', $user->id)->first();
            if (! $profile || ! $profile->studies((string) $code)) {
                throw ExamUnavailable::notAllowed();
            }
        }

        $variant = $this->generator->generate($session, $user);
        $questionIds = $variant->question_ids ?? [];
        if ($questionIds === []) {
            throw ExamUnavailable::noQuestions();
        }

        $expires = $now->copy()->addMinutes(max(1, $session->effectiveDurationMin()));
        if ($session->ends_at !== null && $session->ends_at->lt($expires)) {
            $expires = $session->ends_at->copy();
        }

        $points = ExamBlueprintRule::query()
            ->whereIn('id', array_values(array_unique($variant->rule_map ?? [])))
            ->pluck('points_per_question', 'id');

        try {
            return DB::transaction(function () use ($session, $user, $variant, $questionIds, $now, $expires, $client, $points) {
                $attempt = ExamAttempt::create([
                    'session_id' => $session->id,
                    'variant_id' => $variant->id,
                    'user_id' => $user->id,
                    'session_token' => Str::random(64),
                    'status' => AttemptStatus::InProgress,
                    'started_at' => $now,
                    'expires_at' => $expires,
                    'last_heartbeat_at' => $now,
                    'max_score' => 0,
                    'device_fingerprint' => isset($client['fingerprint']) ? mb_substr((string) $client['fingerprint'], 0, 128) : null,
                    'ip_address' => $client['ip'] ?? null,
                    'user_agent' => isset($client['user_agent']) ? mb_substr((string) $client['user_agent'], 0, 500) : null,
                ]);

                $rows = [];
                $stamp = now();
                foreach (array_values($questionIds) as $i => $questionId) {
                    $ruleId = ($variant->rule_map ?? [])[$questionId] ?? null;
                    $rows[] = [
                        'id' => (string) Str::uuid7(),
                        'attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                        'position' => $i + 1,
                        'max_points' => (float) ($ruleId !== null ? ($points[$ruleId] ?? 1) : 1),
                        'points' => 0,
                        'is_flagged' => false,
                        'time_spent_seconds' => 0,
                        'created_at' => $stamp,
                        'updated_at' => $stamp,
                    ];
                }
                ExamAnswer::query()->insert($rows);

                $attempt->forceFill(['max_score' => array_sum(array_column($rows, 'max_points'))])->save();

                return $attempt;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->resume(
                ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $user->id)->firstOrFail(),
                $now
            );
        }
    }

    /**
     * Batched autosave. $items: [{question_id, selected_option_id?, selected_option_ids?,
     * answer_text?, unit?, is_flagged?, time_spent_seconds?}, ...]
     *
     * @return array{saved: int, remaining_seconds: int}
     */
    public function saveAnswers(ExamAttempt $attempt, array $items, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $this->assertAcceptsAnswers($attempt, $now);

        $attempt->loadMissing('variant');
        $variantIds = array_flip(array_map('strval', $attempt->variant?->question_ids ?? []));
        $items = array_values(array_filter($items, fn ($i) => is_array($i) && isset($variantIds[(string) ($i['question_id'] ?? '')])));
        $questionIds = array_values(array_unique(array_map(fn ($i) => (string) $i['question_id'], $items)));

        $questions = QuestionBank::withTrashed()->whereIn('id', $questionIds)
            ->with('answerOptions:id,question_id')
            ->get(['id', 'question_type'])
            ->keyBy('id');
        $answers = ExamAnswer::query()->where('attempt_id', $attempt->id)->whereIn('question_id', $questionIds)->get()->keyBy('question_id');

        $saved = 0;
        foreach ($items as $item) {
            $question = $questions->get($item['question_id']);
            $answer = $answers->get($item['question_id']);
            if (! $question || ! $answer) {
                continue;
            }

            $this->applyItem($answer, $question, $item, $now);
            if ($answer->isDirty()) {
                $answer->save();
                $saved++;
            }
        }

        $attempt->forceFill(['last_heartbeat_at' => $now])->save();

        return ['saved' => $saved, 'remaining_seconds' => $this->remainingSeconds($attempt, $now)];
    }

    public function submit(ExamAttempt $attempt, bool $auto = false, ?CarbonInterface $now = null): ExamAttempt
    {
        $now ??= now();

        $locked = DB::transaction(function () use ($attempt, $auto, $now) {
            $locked = ExamAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== AttemptStatus::InProgress) {
                return $locked;
            }

            $submittedAt = $now->copy();
            if ($auto && $locked->expires_at !== null && $locked->expires_at->lt($submittedAt)) {
                $submittedAt = $locked->expires_at->copy();
            }

            $locked->forceFill([
                'status' => $auto ? AttemptStatus::AutoSubmitted : AttemptStatus::Submitted,
                'submitted_at' => $submittedAt,
                'time_spent_seconds' => $locked->started_at ? max(0, (int) $locked->started_at->diffInSeconds($submittedAt)) : null,
            ])->save();

            return $locked;
        });

        if (in_array($locked->status, [AttemptStatus::Submitted, AttemptStatus::AutoSubmitted], true)) {
            $this->grading->grade($locked);
        }

        $attempt->setRawAttributes($locked->fresh()->getAttributes(), true);

        return $attempt;
    }

    /** Light anti-cheat log: counted and shown to the teacher, never blocks. */
    public function logEvent(ExamAttempt $attempt, string $type, array $meta = [], ?CarbonInterface $now = null): void
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            return;
        }

        $log = $attempt->violation_log ?? [];
        $log[] = array_filter(['type' => mb_substr($type, 0, 30), 'at' => ($now ?? now())->toIso8601String(), 'meta' => $meta ?: null]);
        $limit = (int) config('exam.engine.violation_log_limit', 100);

        $attempt->forceFill([
            'violations_count' => (int) $attempt->violations_count + 1,
            'violation_log' => array_slice($log, -$limit),
        ])->save();
    }

    /** Submit every in-progress attempt whose deadline (+grace) passed. */
    public function autoSubmitExpired(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $cutoff = $now->copy()->subSeconds((int) config('exam.engine.answer_grace_seconds', 15));
        $count = 0;

        ExamAttempt::query()->expired($cutoff)->orderBy('expires_at')->lazyById(100)->each(function (ExamAttempt $attempt) use ($now, &$count) {
            $this->submit($attempt, true, $now);
            $count++;
        });

        return $count;
    }

    public function remainingSeconds(ExamAttempt $attempt, ?CarbonInterface $now = null): int
    {
        if ($attempt->expires_at === null) {
            return 0;
        }

        return max(0, (int) ($now ?? now())->diffInSeconds($attempt->expires_at, false));
    }

    private function resume(ExamAttempt $attempt, CarbonInterface $now): ExamAttempt
    {
        if ($attempt->status === AttemptStatus::InProgress && $attempt->isExpired($now)) {
            $this->submit($attempt, true, $now);
        }

        if ($attempt->status !== AttemptStatus::InProgress) {
            throw ExamUnavailable::alreadyDone();
        }

        return $attempt;
    }

    private function assertAcceptsAnswers(ExamAttempt $attempt, CarbonInterface $now): void
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            throw ExamUnavailable::attemptClosed();
        }

        $grace = (int) config('exam.engine.answer_grace_seconds', 15);
        if ($attempt->expires_at !== null && $attempt->expires_at->copy()->addSeconds($grace)->lt($now)) {
            $this->submit($attempt, true, $now);
            throw ExamUnavailable::attemptClosed();
        }
    }

    private function applyItem(ExamAnswer $answer, QuestionBank $question, array $item, CarbonInterface $now): void
    {
        $type = $question->examType();
        $optionIds = $question->answerOptions->pluck('id')->map(fn ($id) => (string) $id)->all();
        $contentChanged = false;

        if (array_key_exists('is_flagged', $item)) {
            $answer->is_flagged = (bool) $item['is_flagged'];
        }

        if ($type === ExamQuestionType::MultipleChoice && array_key_exists('selected_option_ids', $item)) {
            $ids = array_values(array_unique(array_filter(
                array_map('strval', (array) ($item['selected_option_ids'] ?? [])),
                fn ($id) => in_array($id, $optionIds, true)
            )));
            sort($ids);
            $answer->selected_option_ids = $ids === [] ? null : $ids;
            $contentChanged = $answer->isDirty('selected_option_ids');
        } elseif ($type?->isChoice() && array_key_exists('selected_option_id', $item)) {
            $id = $item['selected_option_id'] === null ? null : (string) $item['selected_option_id'];
            $answer->selected_option_id = ($id !== null && in_array($id, $optionIds, true)) ? $id : null;
            $contentChanged = $answer->isDirty('selected_option_id');
        } elseif ($type?->usesNumericAnswer()) {
            if (array_key_exists('answer_text', $item)) {
                $text = $item['answer_text'] === null ? null : mb_substr(trim((string) $item['answer_text']), 0, 64);
                $answer->answer_text = $text === '' ? null : $text;
                $parsed = NumberParser::parse($answer->answer_text);
                $answer->numeric_value = $parsed !== null && abs($parsed) < 1e19 ? $parsed : null;
            }
            if ($type->requiresUnit() && array_key_exists('unit', $item)) {
                $unit = $item['unit'] === null ? null : mb_substr(trim((string) $item['unit']), 0, 30);
                $answer->unit = $unit === '' ? null : $unit;
            }
            $contentChanged = $answer->isDirty(['answer_text', 'unit']);
        }

        if (isset($item['time_spent_seconds']) && is_numeric($item['time_spent_seconds'])) {
            $answer->time_spent_seconds = (int) $answer->time_spent_seconds + max(0, min(3600, (int) $item['time_spent_seconds']));
        }

        if ($contentChanged) {
            $answer->answered_at = $now;
        }
    }
}
