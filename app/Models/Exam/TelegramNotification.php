<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message the bot sent (or tried to). (telegram_account_id, kind, ref_id)
 * is unique so reminders and result messages go out once.
 */
class TelegramNotification extends Model
{
    use HasUuids;

    public const KIND_REMINDER_EVE = 'reminder_eve';
    public const KIND_REMINDER_HOUR = 'reminder_hour';
    public const KIND_REMINDER_START = 'reminder_start';
    public const KIND_ATTEMPT_RESULT = 'attempt_result';
    public const KIND_GUARDIAN_RESULT = 'guardian_result';
    public const KIND_WEEKLY_REPORT = 'weekly_report';

    protected $table = 'telegram_notifications';

    protected $fillable = [
        'telegram_account_id',
        'kind',
        'ref_id',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }
}
