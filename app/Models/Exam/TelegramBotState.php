<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conversation state of a Telegram user with the bot (stage 6 registration).
 */
class TelegramBotState extends Model
{
    use HasUuids;

    public const FLOW_REGISTRATION = 'registration';

    protected $table = 'telegram_bot_states';

    protected $fillable = [
        'telegram_account_id',
        'flow',
        'step',
        'data',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }

    public function answer(string $key, mixed $default = null): mixed
    {
        return ($this->data ?? [])[$key] ?? $default;
    }

    public function remember(string $key, mixed $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
    }
}
