<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishTournament extends Model
{
    use HasUuids;

    protected $table = 'english_tournaments';

    protected $fillable = [
        'name',
        'name_uz',
        'description',
        'description_uz',
        'slug',
        'tournament_type',
        'level_id',
        'min_elo',
        'max_elo',
        'min_participants',
        'max_participants',
        'current_participants',
        'registration_start',
        'registration_end',
        'tournament_start',
        'tournament_end',
        'entry_fee_coins',
        'is_free',
        'prizes',
        'settings',
        'winner_id',
        'runner_up_id',
        'third_place_id',
        'banner_image',
        'icon',
        'color',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'min_elo' => 'integer',
        'max_elo' => 'integer',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'tournament_start' => 'datetime',
        'tournament_end' => 'datetime',
        'entry_fee_coins' => 'integer',
        'is_free' => 'boolean',
        'prizes' => 'array',
        'settings' => 'array',
        'is_featured' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'runner_up_id');
    }

    public function thirdPlace(): BelongsTo
    {
        return $this->belongsTo(User::class, 'third_place_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(EnglishTournamentRound::class, 'tournament_id')->orderBy('round_number');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EnglishTournamentParticipant::class, 'tournament_id');
    }

    public function battles(): HasMany
    {
        return $this->hasMany(EnglishBattle::class, 'tournament_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registration', 'ready', 'in_progress']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'registration')
            ->where('registration_end', '>', now());
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration'
            && now()->between($this->registration_start, $this->registration_end)
            && $this->current_participants < $this->max_participants;
    }
}
