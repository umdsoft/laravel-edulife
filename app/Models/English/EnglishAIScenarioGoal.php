<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishAIScenarioGoal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_ai_scenario_goals';

    protected $fillable = [
        'scenario_id',
        'goal_key',
        'title',
        'title_uz',
        'description',
        'detection_keywords',
        'detection_patterns',
        'detection_prompt',
        'points',
        'is_required',
        'is_bonus',
        'order_number',
    ];

    protected $casts = [
        'detection_keywords' => 'array',
        'detection_patterns' => 'array',
        'points' => 'integer',
        'is_required' => 'boolean',
        'is_bonus' => 'boolean',
        'order_number' => 'integer',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(EnglishAIScenario::class, 'scenario_id');
    }
}
