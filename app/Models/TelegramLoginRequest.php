<?php

namespace App\Models;

use App\Models\Exam\TelegramAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pending website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md).
 * Nothing secret is stored in clear: code_hash is an HMAC, token_hash a sha256.
 */
class TelegramLoginRequest extends Model
{
    use HasUuids;

    public const KIND_CODE = 'code';
    public const KIND_LINK = 'link';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const USED = 'used';
    public const CANCELLED = 'cancelled';
    public const REFUSED = 'refused';
    public const EXPIRED = 'expired';

    protected $table = 'telegram_login_requests';

    protected $fillable = [
        'kind', 'user_id', 'telegram_account_id', 'role', 'code_hash', 'token_hash', 'attempts',
        'status', 'reason', 'remember', 'expires_at', 'approved_at', 'used_at',
    ];

    protected $hidden = ['code_hash', 'token_hash'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'remember' => 'boolean',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramAccount(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::PENDING && $this->expires_at->isFuture();
    }
}
