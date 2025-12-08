<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWritingSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_writing_submissions';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'task_type',
        'prompt',
        'prompt_uz',
        'min_words',
        'max_words',
        'content',
        'word_count',
        'sentence_count',
        'paragraph_count',
        'overall_score',
        'grammar_score',
        'vocabulary_score',
        'coherence_score',
        'task_achievement_score',
        'feedback',
        'corrected_content',
        'xp_earned',
        'coins_earned',
        'status',
    ];

    protected $casts = [
        'min_words' => 'integer',
        'max_words' => 'integer',
        'word_count' => 'integer',
        'sentence_count' => 'integer',
        'paragraph_count' => 'integer',
        'overall_score' => 'integer',
        'grammar_score' => 'integer',
        'vocabulary_score' => 'integer',
        'coherence_score' => 'integer',
        'task_achievement_score' => 'integer',
        'feedback' => 'array',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'lesson_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('task_type', $type);
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzed');
    }
}
