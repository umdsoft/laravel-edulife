<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVocabulary extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_vocabulary';

    protected $fillable = [
        'user_id',
        'vocabulary_id',
        'status',
        'ease_factor',
        'interval_days',
        'repetitions',
        'next_review_date',
        'last_review_date',
        'quality_history',
        'times_seen',
        'times_correct',
        'times_incorrect',
        'consecutive_correct',
        'mastery_level',
        'learned_in_lesson_id',
        'first_seen_at',
        'mastered_at',
        'user_notes',
        'is_favorite',
        'is_difficult',
    ];

    protected $casts = [
        'ease_factor' => 'decimal:2',
        'interval_days' => 'integer',
        'repetitions' => 'integer',
        'next_review_date' => 'date',
        'last_review_date' => 'date',
        'quality_history' => 'array',
        'times_seen' => 'integer',
        'times_correct' => 'integer',
        'times_incorrect' => 'integer',
        'consecutive_correct' => 'integer',
        'mastery_level' => 'integer',
        'first_seen_at' => 'datetime',
        'mastered_at' => 'datetime',
        'is_favorite' => 'boolean',
        'is_difficult' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    public function learnedInLesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'learned_in_lesson_id');
    }

    public function scopeDueForReview($query)
    {
        return $query->whereNotNull('next_review_date')
            ->where('next_review_date', '<=', now()->toDateString());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * SM-2 Algorithm implementation
     */
    public function processReview(int $quality): void
    {
        $history = $this->quality_history ?? [];
        $history[] = $quality;
        $this->quality_history = array_slice($history, -10);

        $this->last_review_date = now()->toDateString();
        $this->times_seen++;

        if ($quality >= 3) {
            $this->times_correct++;
            $this->consecutive_correct++;

            if ($this->repetitions === 0) {
                $this->interval_days = 1;
            } elseif ($this->repetitions === 1) {
                $this->interval_days = 6;
            } else {
                $this->interval_days = (int) round($this->interval_days * $this->ease_factor);
            }

            $this->repetitions++;
        } else {
            $this->times_incorrect++;
            $this->consecutive_correct = 0;
            $this->repetitions = 0;
            $this->interval_days = 1;
        }

        $this->ease_factor = max(1.3, $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)));
        $this->next_review_date = now()->addDays($this->interval_days)->toDateString();
        $this->updateStatusAndMastery();
        $this->save();
    }

    private function updateStatusAndMastery(): void
    {
        if ($this->consecutive_correct >= 5 && $this->interval_days >= 32) {
            $this->status = 'mastered';
            $this->mastery_level = 5;
            $this->mastered_at = $this->mastered_at ?? now();
        } elseif ($this->interval_days >= 16) {
            $this->status = 'reviewing';
            $this->mastery_level = 4;
        } elseif ($this->interval_days >= 8) {
            $this->status = 'reviewing';
            $this->mastery_level = 3;
        } elseif ($this->repetitions >= 2) {
            $this->status = 'learning';
            $this->mastery_level = 2;
        } elseif ($this->repetitions >= 1) {
            $this->status = 'learning';
            $this->mastery_level = 1;
        } else {
            $this->status = 'new';
            $this->mastery_level = 0;
        }
    }

    public function getAccuracyRateAttribute(): float
    {
        if ($this->times_seen === 0)
            return 0;
        return round(($this->times_correct / $this->times_seen) * 100, 1);
    }
}
