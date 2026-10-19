<?php

namespace App\Models\Exam\Concerns;

use App\Enums\Exam\BlueprintPart;
use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\QuestionOrigin;
use App\Enums\Exam\ReviewStatus;
use App\Exceptions\Exam\InvalidReviewTransition;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\QuestionErrorReport;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use App\Services\Exam\QuestionApprovalGuard;
use App\Support\Exam\QuestionText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exam module behaviour of App\Models\QuestionBank (kept in a trait so the
 * legacy olympiad model stays almost untouched).
 *
 * Invariants enforced on save:
 *  - review_status changes only along ReviewStatus::allowedTransitions();
 *  - a question becomes APPROVED only when QuestionApprovalGuard passes
 *    (e.g. single_choice has exactly one option marked correct);
 *  - normalized_hash follows question_text (duplicate detection).
 */
trait ExamQuestion
{
    public function initializeExamQuestion(): void
    {
        $this->mergeFillable([
            'subject_id',
            'difficulty_level',
            'exam_formats',
            'source_ref',
            'origin',
            'review_status',
            'reviewed_by',
            'reviewed_at',
            'reject_reason',
            'solution_video_url',
            'solution_text',
            'content_format',
            'units_note',
            'constants',
            'error_reports_count',
            'is_demo',
            'normalized_hash',
        ]);

        $this->mergeCasts([
            'difficulty_level' => 'integer',
            'exam_formats' => 'array',
            'origin' => QuestionOrigin::class,
            'review_status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
            'constants' => 'array',
            'error_reports_count' => 'integer',
            'is_demo' => 'boolean',
        ]);
    }

    public static function bootExamQuestion(): void
    {
        static::saving(function (self $question) {
            if ($question->isDirty('question_text')) {
                $question->normalized_hash = QuestionText::hash((string) $question->question_text);
            }

            if (! $question->isDirty('review_status') || $question->review_status === null) {
                return;
            }

            $to = $question->review_status;
            $raw = $question->getRawOriginal('review_status');
            $from = $question->exists && $raw !== null ? ReviewStatus::from($raw) : null;

            if ($from !== null && ! $from->canTransitionTo($to)) {
                throw InvalidReviewTransition::between($from, $to);
            }

            if ($to === ReviewStatus::Approved) {
                app(QuestionApprovalGuard::class)->assertApprovable($question);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Options A..E (the legacy `options` JSON attribute is kept for olympiads). */
    public function answerOptions(): HasMany
    {
        return $this->hasMany(QuestionBankOption::class, 'question_id')->orderBy('sort_order')->orderBy('label');
    }

    /** Expected answers of numeric / numeric_with_unit questions. */
    public function numericAnswers(): HasMany
    {
        return $this->hasMany(QuestionBankAnswer::class, 'question_id')->orderBy('sort_order');
    }

    public function errorReports(): HasMany
    {
        return $this->hasMany(QuestionErrorReport::class, 'question_id');
    }

    // ==================== SCOPES ====================

    /** Approved questions: the only ones an exam variant may contain. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('review_status', ReviewStatus::Approved->value);
    }

    /** Approved, classified (subject + topic + difficulty) and gradable by the exam engine. */
    public function scopeExamEligible(Builder $query): Builder
    {
        return $query->approved()
            ->whereNotNull('subject_id')
            ->whereNotNull('topic_id')
            ->whereNotNull('difficulty_level')
            ->whereIn('question_type', [
                ...ExamQuestionType::valuesForPart(BlueprintPart::Closed),
                ...ExamQuestionType::valuesForPart(BlueprintPart::Open),
            ]);
    }

    public function scopeReviewQueue(Builder $query): Builder
    {
        return $query->where('review_status', ReviewStatus::InReview->value)->orderBy('created_at');
    }

    public function scopeReviewStatus(Builder $query, ReviewStatus $status): Builder
    {
        return $query->where('review_status', $status->value);
    }

    public function scopeForSubject(Builder $query, Subject|string $subject): Builder
    {
        return $query->where('subject_id', $subject instanceof Subject ? $subject->id : $subject);
    }

    /** Questions of a topic and of all of its descendants. */
    public function scopeInTopicTree(Builder $query, Topic $topic): Builder
    {
        return $query->whereIn('topic_id', $topic->descendantAndSelfIds());
    }

    public function scopeDifficultyLevel(Builder $query, ?int $level): Builder
    {
        return $level === null ? $query : $query->where('difficulty_level', $level);
    }

    public function scopeForPart(Builder $query, BlueprintPart $part): Builder
    {
        return $query->whereIn('question_type', ExamQuestionType::valuesForPart($part));
    }

    public function scopeDemo(Builder $query, bool $demo = true): Builder
    {
        return $query->where('is_demo', $demo);
    }

    // ==================== HELPERS ====================

    public function examType(): ?ExamQuestionType
    {
        return ExamQuestionType::tryFrom((string) $this->question_type);
    }

    public function isApproved(): bool
    {
        return $this->review_status === ReviewStatus::Approved;
    }
}
