<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'correct_answer',
        'is_answered',
        'is_correct',
        'points_earned',
        'max_points',
        'time_spent',
        'answered_at',
        'is_flagged_for_review',
    ];
    
    protected $casts = [
        'answer' => 'array',
        'correct_answer' => 'array',
        'is_answered' => 'boolean',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'max_points' => 'decimal:2',
        'time_spent' => 'integer',
        'answered_at' => 'datetime',
        'is_flagged_for_review' => 'boolean',
    ];
    
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }
    
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
