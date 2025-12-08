<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUnitProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_unit_progress';

    protected $fillable = [
        'user_id',
        'unit_id',
        'status',
        'lessons_completed',
        'total_lessons',
        'completion_percentage',
        'unit_test_score',
        'unit_test_attempts',
        'unit_test_passed',
        'unit_test_completed_at',
        'xp_earned',
        'coins_earned',
        'badge_earned',
        'time_spent_seconds',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'lessons_completed' => 'integer',
        'total_lessons' => 'integer',
        'completion_percentage' => 'decimal:2',
        'unit_test_score' => 'integer',
        'unit_test_attempts' => 'integer',
        'unit_test_passed' => 'boolean',
        'unit_test_completed_at' => 'datetime',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'badge_earned' => 'boolean',
        'time_spent_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EnglishUnit::class, 'unit_id');
    }
}
