<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudySession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_study_sessions';

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'activity_type',
        'lesson_id',
        'game_id',
        'xp_earned',
        'coins_earned',
        'words_reviewed',
        'exercises_completed',
        'device_type',
        'platform',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'words_reviewed' => 'integer',
        'exercises_completed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'lesson_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(EnglishGame::class, 'game_id');
    }
}
