<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Result of one student in one session: the growth history
 * (delta_prev = percent points vs the previous result of the same subject).
 */
class StudentWeeklyResult extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_student_weekly_results';

    protected $fillable = [
        'user_id',
        'session_id',
        'attempt_id',
        'subject_id',
        'week_start',
        'score',
        'max_score',
        'score_percent',
        'delta_prev',
        'rank',
        'percentile',
        'participants_count',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'delta_prev' => 'decimal:2',
            'rank' => 'integer',
            'percentile' => 'decimal:2',
            'participants_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
