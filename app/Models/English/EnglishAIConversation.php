<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishAIConversation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_ai_conversations';

    protected $fillable = [
        'user_id',
        'scenario_id',
        'conversation_type',
        'level_code',
        'topic',
        'settings',
        'status',
        'message_count',
        'user_message_count',
        'duration_seconds',
        'goals_completed',
        'goals_score',
        'analysis',
        'xp_earned',
        'coins_earned',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'message_count' => 'integer',
        'user_message_count' => 'integer',
        'duration_seconds' => 'integer',
        'goals_completed' => 'array',
        'goals_score' => 'integer',
        'analysis' => 'array',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(EnglishAIScenario::class, 'scenario_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EnglishAIMessage::class, 'conversation_id')->orderBy('order_number');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
