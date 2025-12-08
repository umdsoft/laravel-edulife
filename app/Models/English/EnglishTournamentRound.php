<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishTournamentRound extends Model
{
    use HasUuids;

    protected $table = 'english_tournament_rounds';

    protected $fillable = [
        'tournament_id',
        'round_number',
        'round_name',
        'round_name_uz',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'total_matches',
        'completed_matches',
    ];

    protected $casts = [
        'round_number' => 'integer',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'total_matches' => 'integer',
        'completed_matches' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(EnglishTournament::class, 'tournament_id');
    }

    public function battles(): HasMany
    {
        return $this->hasMany(EnglishBattle::class, 'tournament_round_id');
    }
}
