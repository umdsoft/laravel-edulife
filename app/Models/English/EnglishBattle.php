<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishBattle extends Model
{
    use HasUuids;

    protected $table = 'english_battles';

    protected $fillable = [
        'player1_id',
        'player2_id',
        'battle_type',
        'level_id',
        'topic_focus',
        'player1_elo_before',
        'player2_elo_before',
        'player1_elo_after',
        'player2_elo_after',
        'elo_change',
        'player1_score',
        'player2_score',
        'player1_correct',
        'player2_correct',
        'player1_total_time_ms',
        'player2_total_time_ms',
        'player1_avg_time',
        'player2_avg_time',
        'winner_id',
        'result',
        'settings',
        'winner_xp',
        'loser_xp',
        'winner_coins',
        'loser_coins',
        'status',
        'tournament_id',
        'tournament_round_id',
        'matched_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'player1_elo_before' => 'integer',
        'player2_elo_before' => 'integer',
        'player1_elo_after' => 'integer',
        'player2_elo_after' => 'integer',
        'elo_change' => 'integer',
        'player1_score' => 'integer',
        'player2_score' => 'integer',
        'player1_correct' => 'integer',
        'player2_correct' => 'integer',
        'player1_total_time_ms' => 'integer',
        'player2_total_time_ms' => 'integer',
        'player1_avg_time' => 'decimal:2',
        'player2_avg_time' => 'decimal:2',
        'settings' => 'array',
        'winner_xp' => 'integer',
        'loser_xp' => 'integer',
        'winner_coins' => 'integer',
        'loser_coins' => 'integer',
        'matched_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(EnglishTournament::class, 'tournament_id');
    }

    public function tournamentRound(): BelongsTo
    {
        return $this->belongsTo(EnglishTournamentRound::class, 'tournament_round_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(EnglishBattleRound::class, 'battle_id')->orderBy('round_number');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'ready', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('player1_id', $userId)->orWhere('player2_id', $userId);
        });
    }

    public function isUserPlayer(string $userId): bool
    {
        return $this->player1_id === $userId || $this->player2_id === $userId;
    }

    public function getOpponentId(string $userId): ?string
    {
        if ($this->player1_id === $userId)
            return $this->player2_id;
        if ($this->player2_id === $userId)
            return $this->player1_id;
        return null;
    }

    public function calculateEloChange(): int
    {
        $K = 32;
        $expected1 = 1 / (1 + pow(10, ($this->player2_elo_before - $this->player1_elo_before) / 400));

        $actual1 = match ($this->result) {
            'player1_win' => 1,
            'player2_win' => 0,
            'draw' => 0.5,
            default => 0.5,
        };

        return (int) round($K * ($actual1 - $expected1));
    }
}
