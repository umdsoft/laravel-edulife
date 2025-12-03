<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'name', 'description', 'image', 'type', 'format',
        'direction_id', 'max_participants', 'min_participants',
        'rounds_per_match', 'time_per_question',
        'min_level', 'entry_fee', 'prizes',
        'status', 'current_round',
        'registration_starts_at', 'registration_ends_at', 'starts_at', 'ends_at',
    ];
    
    protected $casts = [
        'max_participants' => 'integer',
        'min_participants' => 'integer',
        'rounds_per_match' => 'integer',
        'time_per_question' => 'integer',
        'min_level' => 'integer',
        'entry_fee' => 'integer',
        'prizes' => 'array',
        'current_round' => 'integer',
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
    
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration' &&
            now()->between($this->registration_starts_at, $this->registration_ends_at) &&
            $this->participants()->count() < $this->max_participants;
    }
    
    public function getPrizeForPosition(int $position): array
    {
        return $this->prizes[$position] ?? ['xp' => 0, 'coins' => 0];
    }
}
