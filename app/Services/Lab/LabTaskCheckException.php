<?php

namespace App\Services\Lab;

use RuntimeException;

/**
 * A task check that cannot be performed at all (not a wrong answer):
 * foreign attempt, finished attempt, unknown task step.
 * The message is user-facing (Uzbek); status() is the HTTP status to answer with.
 */
class LabTaskCheckException extends RuntimeException
{
    public const NOT_OWNER = 'not_owner';
    public const NOT_IN_PROGRESS = 'not_in_progress';
    public const UNKNOWN_TASK = 'unknown_task';

    private const STATUS = [
        self::NOT_OWNER => 403,
        self::NOT_IN_PROGRESS => 409,
        self::UNKNOWN_TASK => 422,
    ];

    public function __construct(private readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function status(): int
    {
        return self::STATUS[$this->reason] ?? 422;
    }

    public static function notOwner(): self
    {
        return new self(self::NOT_OWNER, 'Bu urinish sizga tegishli emas.');
    }

    public static function notInProgress(): self
    {
        return new self(self::NOT_IN_PROGRESS, 'Bu urinish yakunlangan yoki faol emas. Tajribani qaytadan boshlang.');
    }

    public static function unknownTask(int $step): self
    {
        return new self(self::UNKNOWN_TASK, "Bu tajribada {$step}-vazifa mavjud emas.");
    }
}
