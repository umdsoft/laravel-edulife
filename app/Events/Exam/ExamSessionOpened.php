<?php

namespace App\Events\Exam;

use App\Models\Exam\ExamSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A scheduled exam session became open (status scheduled -> open), fired
 * exactly once per session by ExamSessionLifecycle.
 */
class ExamSessionOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ExamSession $session,
    ) {}
}
