<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_skill_levels';

    protected $fillable = [
        'user_id',
        'skill',
        'level',
        'xp',
        'xp_to_next_level',
        'exercises_completed',
        'correct_answers',
        'total_answers',
        'accuracy',
        'estimated_cefr',
        'weekly_progress',
        'last_activity_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp' => 'integer',
        'xp_to_next_level' => 'integer',
        'exercises_completed' => 'integer',
        'correct_answers' => 'integer',
        'total_answers' => 'integer',
        'accuracy' => 'decimal:2',
        'weekly_progress' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addXp(int $amount): void
    {
        $this->xp += $amount;

        while ($this->xp >= $this->xp_to_next_level) {
            $this->xp -= $this->xp_to_next_level;
            $this->level++;
            $this->xp_to_next_level = $this->calculateNextLevelXp();
        }

        $this->last_activity_at = now();
        $this->save();
    }

    private function calculateNextLevelXp(): int
    {
        return 100 + ($this->level * 20);
    }
}
