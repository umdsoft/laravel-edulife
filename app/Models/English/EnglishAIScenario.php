<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishAIScenario extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_ai_scenarios';

    protected $fillable = [
        'level_id',
        'slug',
        'code',
        'title',
        'title_uz',
        'description',
        'description_uz',
        'category',
        'scenario_config',
        'ai_config',
        'min_xp',
        'prerequisite_vocabulary',
        'prerequisite_grammar',
        'xp_reward',
        'coin_reward',
        'icon',
        'thumbnail',
        'background_image',
        'times_played',
        'average_score',
        'average_duration',
        'order_number',
        'is_premium',
        'is_active',
    ];

    protected $casts = [
        'scenario_config' => 'array',
        'ai_config' => 'array',
        'min_xp' => 'integer',
        'prerequisite_vocabulary' => 'array',
        'prerequisite_grammar' => 'array',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'times_played' => 'integer',
        'average_score' => 'decimal:2',
        'average_duration' => 'decimal:2',
        'order_number' => 'integer',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(EnglishAIScenarioGoal::class, 'scenario_id')->orderBy('order_number');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(EnglishAIConversation::class, 'scenario_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->scenario_config, $key, $default);
    }
}
