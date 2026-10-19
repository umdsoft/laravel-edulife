<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Telegram bot / Mini App identity. user_id is NULL until the Telegram user
 * registers or links an existing account.
 */
class TelegramAccount extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'telegram_accounts';

    protected $fillable = [
        'user_id',
        'telegram_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'is_blocked',
        'start_payload',
        'linked_at',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'chat_id' => 'integer',
            'is_blocked' => 'boolean',
            'linked_at' => 'datetime',
            'last_interaction_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLinked(): bool
    {
        return $this->user_id !== null;
    }
}
