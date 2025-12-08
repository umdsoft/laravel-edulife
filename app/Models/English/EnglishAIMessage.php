<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishAIMessage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'input_type',
        'audio_url',
        'audio_duration_ms',
        'grammar_check',
        'vocabulary_used',
        'new_vocabulary',
        'suggestions',
        'word_count',
        'response_time_ms',
        'order_number',
    ];

    protected $casts = [
        'audio_duration_ms' => 'integer',
        'grammar_check' => 'array',
        'vocabulary_used' => 'array',
        'new_vocabulary' => 'array',
        'suggestions' => 'array',
        'word_count' => 'integer',
        'response_time_ms' => 'integer',
        'order_number' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EnglishAIConversation::class, 'conversation_id');
    }

    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }
}
