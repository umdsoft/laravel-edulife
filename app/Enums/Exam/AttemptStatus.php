<?php

namespace App\Enums\Exam;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    /** Submitted by the server after the deadline. */
    case AutoSubmitted = 'auto_submitted';
    case Graded = 'graded';
    /** Cancelled by anti-cheat or the teacher. */
    case Invalidated = 'invalidated';

    public function isFinished(): bool
    {
        return $this !== self::InProgress;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
