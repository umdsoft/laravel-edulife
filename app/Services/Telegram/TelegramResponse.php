<?php

namespace App\Services\Telegram;

/**
 * Result of a Bot API call. skipped = the bot is not configured (nothing sent).
 */
final class TelegramResponse
{
    public function __construct(
        public readonly bool $ok,
        public readonly int $status = 0,
        public readonly ?int $errorCode = null,
        public readonly ?string $description = null,
        public readonly mixed $result = null,
        public readonly bool $skipped = false,
    ) {}

    public static function skipped(): self
    {
        return new self(false, skipped: true);
    }

    /** 403 "bot was blocked by the user" / "user is deactivated". */
    public function isBlocked(): bool
    {
        return $this->errorCode === 403;
    }
}
