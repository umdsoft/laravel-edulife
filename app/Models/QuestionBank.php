<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'question_bank';

    // Question type constants
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_FILL_BLANK = 'fill_blank';
    public const TYPE_MATCHING = 'matching';
    public const TYPE_ORDERING = 'ordering';
    public const TYPE_ESSAY = 'essay';
    public const TYPE_SHORT_ANSWER = 'short_answer';
    public const TYPE_AUDIO_RESPONSE = 'audio_response';

    // Difficulty constants
    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';
    public const DIFFICULTY_EXPERT = 'expert';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'olympiad_type_id',
        'section_type',
        'direction_id',
        'topic_id',
        'question_type',
        'question_text',
        'question_html',
        'question_media',
        'options',
        'correct_answer',
        'explanation',
        'explanation_media',
        'difficulty',
        'base_points',
        'estimated_time_seconds',
        'negative_marking',
        'negative_points',
        'requires_manual_grading',
        'grading_rubric',
        'concept_tags',
        'skill_tags',
        'times_used',
        'times_answered',
        'times_correct',
        'status',
        'is_verified',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'question_media' => 'array',
            'options' => 'array',
            'correct_answer' => 'array',
            'explanation_media' => 'array',
            'grading_rubric' => 'array',
            'concept_tags' => 'array',
            'skill_tags' => 'array',
            'base_points' => 'integer',
            'estimated_time_seconds' => 'integer',
            'negative_marking' => 'boolean',
            'negative_points' => 'decimal:2',
            'requires_manual_grading' => 'boolean',
            'times_used' => 'integer',
            'times_answered' => 'integer',
            'times_correct' => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiadType(): BelongsTo
    {
        return $this->belongsTo(OlympiadType::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function olympiadQuestions(): HasMany
    {
        return $this->hasMany(OlympiadQuestion::class, 'question_id');
    }

    public function demoQuestions(): HasMany
    {
        return $this->hasMany(OlympiadDemoQuestion::class, 'question_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, string $typeId)
    {
        return $query->where('olympiad_type_id', $typeId);
    }

    public function scopeBySection($query, string $sectionType)
    {
        return $query->where('section_type', $sectionType);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeAutoGraded($query)
    {
        return $query->where('requires_manual_grading', false);
    }

    public function scopeManualGraded($query)
    {
        return $query->where('requires_manual_grading', true);
    }

    // ==================== ACCESSORS ====================

    public function getIsChoiceQuestionAttribute(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_SINGLE_CHOICE,
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_TRUE_FALSE,
        ]);
    }

    public function getIsTextQuestionAttribute(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_FILL_BLANK,
            self::TYPE_SHORT_ANSWER,
            self::TYPE_ESSAY,
        ]);
    }

    public function getIsInteractiveQuestionAttribute(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_MATCHING,
            self::TYPE_ORDERING,
        ]);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->times_answered === 0) {
            return 0;
        }
        return round(($this->times_correct / $this->times_answered) * 100, 2);
    }

    public function getHasAudioAttribute(): bool
    {
        return isset($this->question_media['audio_url']);
    }

    public function getHasImageAttribute(): bool
    {
        return isset($this->question_media['image_url']);
    }

    public function getHasPassageAttribute(): bool
    {
        return isset($this->question_media['passage']);
    }

    // ==================== METHODS ====================

    /**
     * Check if user answer is correct
     */
    public function checkAnswer($userAnswer): bool
    {
        if ($this->requires_manual_grading) {
            return false; // Manual grading needed
        }

        $correctAnswer = $this->correct_answer;

        switch ($this->question_type) {
            case self::TYPE_SINGLE_CHOICE:
            case self::TYPE_TRUE_FALSE:
                return $userAnswer === $correctAnswer;
                
            case self::TYPE_MULTIPLE_CHOICE:
                $userAnswerArray = is_array($userAnswer) ? $userAnswer : [$userAnswer];
                $correctArray = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
                sort($userAnswerArray);
                sort($correctArray);
                return $userAnswerArray === $correctArray;
                
            case self::TYPE_FILL_BLANK:
            case self::TYPE_SHORT_ANSWER:
                $userAnswerNormalized = strtolower(trim($userAnswer));
                $acceptableAnswers = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
                foreach ($acceptableAnswers as $acceptable) {
                    if (strtolower(trim($acceptable)) === $userAnswerNormalized) {
                        return true;
                    }
                }
                return false;
                
            case self::TYPE_MATCHING:
            case self::TYPE_ORDERING:
                return $userAnswer === $correctAnswer;
                
            default:
                return false;
        }
    }

    /**
     * Calculate points earned for an answer
     */
    public function calculatePoints($userAnswer): float
    {
        if ($this->checkAnswer($userAnswer)) {
            return $this->base_points;
        }

        if ($this->negative_marking && $userAnswer !== null) {
            return -$this->negative_points;
        }

        return 0;
    }

    /**
     * Increment usage statistics
     */
    public function recordUsage(bool $wasCorrect = false): void
    {
        $this->times_used++;
        $this->times_answered++;
        if ($wasCorrect) {
            $this->times_correct++;
        }
        $this->save();
    }
}
