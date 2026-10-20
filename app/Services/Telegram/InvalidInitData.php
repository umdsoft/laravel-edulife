<?php

namespace App\Services\Telegram;

use RuntimeException;

/**
 * Telegram Mini App initData failed verification. $reason is a short code
 * (not_configured | malformed | missing_hash | bad_hash | expired | no_user).
 */
class InvalidInitData extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("Telegram initData rejected: {$reason}");
    }
}
