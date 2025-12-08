<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyChallenge extends Model
{
    use HasUuids;

    protected $table = 'user_daily_challenges';

    protected $fillable = [
        'user_id',
        'daily_challenge_id',
        'tasks_progress',
        'tasks_completed',
        'total_tasks',
        'all_completed',
        'xp_earned',
        'coins_earned',
        'bonus_claimed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'tasks_progress' => 'array',
        'tasks_completed' => 'integer',
        'total_tasks' => 'integer',
        'all_completed' => 'boolean',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'bonus_claimed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyChallenge(): BelongsTo
    {
        return $this->belongsTo(EnglishDailyChallenge::class, 'daily_challenge_id');
    }

    public function updateTaskProgress(string $taskId, int $progress): void
    {
        $tasksProgress = $this->tasks_progress ?? [];

        if (isset($tasksProgress[$taskId])) {
            $tasksProgress[$taskId]['current'] = min($progress, $tasksProgress[$taskId]['required']);
            $tasksProgress[$taskId]['completed'] = $tasksProgress[$taskId]['current'] >= $tasksProgress[$taskId]['required'];
        }

        $this->tasks_progress = $tasksProgress;
        $this->tasks_completed = collect($tasksProgress)->where('completed', true)->count();
        $this->all_completed = $this->tasks_completed >= $this->total_tasks;

        if ($this->all_completed && !$this->completed_at) {
            $this->completed_at = now();
        }

        $this->save();
    }
}
