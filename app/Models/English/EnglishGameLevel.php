<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishGameLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_game_levels';

    protected $fillable = [
        'game_id',
        'english_level_id',
        'level_number',
        'name',
        'name_uz',
        'difficulty',
        'level_config',
        'stars_required',
        'xp_required',
        'xp_reward',
        'coin_reward',
        'max_stars',
        'pass_score',
        'three_star_score',
        'two_star_score',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'level_config' => 'array',
        'level_number' => 'integer',
        'stars_required' => 'integer',
        'xp_required' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'max_stars' => 'integer',
        'pass_score' => 'integer',
        'three_star_score' => 'integer',
        'two_star_score' => 'integer',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(EnglishGame::class, 'game_id');
    }

    public function englishLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'english_level_id');
    }

    public function content(): HasMany
    {
        return $this->hasMany(EnglishGameContent::class, 'game_level_id');
    }

    public function calculateStars(int $score): int
    {
        if ($score >= $this->three_star_score)
            return 3;
        if ($score >= $this->two_star_score)
            return 2;
        if ($score >= $this->pass_score)
            return 1;
        return 0;
    }
}
