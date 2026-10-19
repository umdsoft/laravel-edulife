<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mastery (0..100) of one student in one topic - the "topic map".
 * Update rule: see docs/exam/02_DATA_MODEL.md (implemented in stage 5).
 */
class StudentTopicMastery extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_student_topic_mastery';

    protected $fillable = [
        'user_id',
        'topic_id',
        'subject_id',
        'score',
        'attempts_count',
        'answered_count',
        'correct_count',
        'source',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'attempts_count' => 'integer',
            'answered_count' => 'integer',
            'correct_count' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentTopicMastery $mastery) {
            $score = (float) $mastery->score;
            if ($score < 0 || $score > 100) {
                throw new \InvalidArgumentException('Mastery score must be within 0..100.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
