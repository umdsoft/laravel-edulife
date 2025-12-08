<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishGame extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_games';

    protected $fillable = [
        'category_id',
        'slug',
        'code',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'instructions',
        'instructions_uz',
        'game_type',
        'skills_focus',
        'game_config',
        'min_level_id',
        'min_xp',
        'xp_reward_min',
        'xp_reward_max',
        'coin_reward_min',
        'coin_reward_max',
        'icon',
        'color',
        'thumbnail',
        'banner_image',
        'total_plays',
        'average_score',
        'average_time',
        'is_premium',
        'is_featured',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'skills_focus' => 'array',
        'game_config' => 'array',
        'min_xp' => 'integer',
        'xp_reward_min' => 'integer',
        'xp_reward_max' => 'integer',
        'coin_reward_min' => 'integer',
        'coin_reward_max' => 'integer',
        'total_plays' => 'integer',
        'average_score' => 'decimal:2',
        'average_time' => 'decimal:2',
        'is_premium' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EnglishGameCategory::class, 'category_id');
    }

    public function minLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'min_level_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(EnglishGameLevel::class, 'game_id')->orderBy('level_number');
    }

    public function content(): HasMany
    {
        return $this->hasMany(EnglishGameContent::class, 'game_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(EnglishGameReward::class, 'game_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserGameAttempt::class, 'game_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('game_type', $type);
    }

    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->game_config, $key, $default);
    }
}
