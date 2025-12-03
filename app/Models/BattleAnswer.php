<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BattleAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'battle_id',
        'battle_question_id',
        'user_id',
        'answer',
        'is_correct',
        'points_earned',
        'time_taken_ms',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    // Relationships
    public function battle()
    {
        return $this->belongsTo(Battle::class);
    }

    public function battleQuestion()
    {
        return $this->belongsTo(BattleQuestion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
