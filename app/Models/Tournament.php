<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasUuids;
    
    // columns of the tournaments table (the code used to read entry_fee / min_level, which don't exist)
    protected $fillable = [
        'name', 'slug', 'description', 'type', 'format',
        'direction_id', 'course_id', 'max_participants', 'min_participants', 'current_participants',
        'min_subscription', 'min_elo', 'max_elo',
        'entry_fee_coins', 'prize_pool_coins', 'prizes',
        'questions_per_match', 'time_per_question',
        'status',
        'registration_starts_at', 'registration_ends_at', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'max_participants' => 'integer',
        'min_participants' => 'integer',
        'questions_per_match' => 'integer',
        'time_per_question' => 'integer',
        'min_elo' => 'integer',
        'max_elo' => 'integer',
        'entry_fee_coins' => 'integer',
        'prize_pool_coins' => 'integer',
        'prizes' => 'array',
        'registration_starts_at' => 'datetime',
        'registration_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
    
    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }
    
    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }
    
    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }
    
    /** Coins charged on registration. */
    public function getEntryFeeAttribute(): int
    {
        return (int) ($this->entry_fee_coins ?? 0);
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'registration') {
            return false;
        }
        if ($this->registration_starts_at && now()->lt($this->registration_starts_at)) {
            return false;
        }
        if ($this->registration_ends_at && now()->gt($this->registration_ends_at)) {
            return false;
        }

        return ! $this->max_participants || $this->participants()->count() < $this->max_participants;
    }

    /**
     * Why the student cannot join (ELO bracket), or null.
     */
    public function eligibilityError(?int $elo): ?string
    {
        $elo = (int) ($elo ?? 1000);
        if ($this->min_elo && $elo < $this->min_elo) {
            return "Bu turnir {$this->min_elo}+ ELO reytingli o'quvchilar uchun.";
        }
        if ($this->max_elo && $elo > $this->max_elo) {
            return "Bu turnir {$this->max_elo} ELO gacha bo'lgan o'quvchilar uchun.";
        }

        return null;
    }

    public function getPrizeForPosition(int $position): array
    {
        return $this->prizes[$position] ?? ['xp' => 0, 'coins' => 0];
    }
}
