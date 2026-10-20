<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One AI drafting run (stage 4): request, prompt version, token usage,
 * estimated cost and outcome. Table exam_ai_draft_runs.
 */
class QuestionDraftRun extends Model
{
    use HasUuids;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_PARTIAL = 'partial';     // some items failed validation
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';     // not configured / budget reached / no sources

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULE = 'schedule';

    protected $table = 'exam_ai_draft_runs';

    protected $fillable = [
        'subject_id',
        'topic_id',
        'difficulty_level',
        'exam_format',
        'question_type',
        'count_requested',
        'source_document_ids',
        'source_excerpts',
        'trigger',
        'status',
        'model',
        'response_model',
        'prompt_name',
        'prompt_version',
        'input_tokens',
        'output_tokens',
        'cache_creation_input_tokens',
        'cache_read_input_tokens',
        'estimated_cost_usd',
        'stop_reason',
        'drafts_created',
        'drafts_rejected',
        'duplicates_skipped',
        'question_ids',
        'validation_errors',
        'error',
        'requested_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_level' => 'integer',
            'count_requested' => 'integer',
            'source_document_ids' => 'array',
            'source_excerpts' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cache_creation_input_tokens' => 'integer',
            'cache_read_input_tokens' => 'integer',
            'estimated_cost_usd' => 'decimal:6',
            'drafts_created' => 'integer',
            'drafts_rejected' => 'integer',
            'duplicates_skipped' => 'integer',
            'question_ids' => 'array',
            'validation_errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCEEDED, self::STATUS_PARTIAL, self::STATUS_FAILED, self::STATUS_SKIPPED], true);
    }

    /** Runs created since $from (cost totals, weekly budget). */
    public function scopeSince(Builder $query, \DateTimeInterface $from): Builder
    {
        return $query->where('created_at', '>=', $from);
    }

    public function markFinished(string $status, array $attributes = []): void
    {
        $this->fill(['status' => $status, 'finished_at' => now(), ...$attributes])->save();
    }
}
