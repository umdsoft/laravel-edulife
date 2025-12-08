<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPronunciationAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_pronunciation_attempts';

    protected $fillable = [
        'user_id',
        'pronunciation_word_id',
        'audio_url',
        'audio_duration_ms',
        'overall_score',
        'pronunciation_score',
        'fluency_score',
        'intonation_score',
        'analysis',
        'stars_earned',
        'xp_earned',
    ];

    protected $casts = [
        'audio_duration_ms' => 'integer',
        'overall_score' => 'integer',
        'pronunciation_score' => 'integer',
        'fluency_score' => 'integer',
        'intonation_score' => 'integer',
        'analysis' => 'array',
        'stars_earned' => 'integer',
        'xp_earned' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pronunciationWord(): BelongsTo
    {
        return $this->belongsTo(EnglishPronunciationWord::class, 'pronunciation_word_id');
    }
}
