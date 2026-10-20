<?php

namespace App\Jobs\Exam;

use App\Services\Exam\ExamAttemptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * AutoSubmitExpiredTestJob pattern for exam attempts: every in-progress
 * attempt past its server deadline (+ grace) is submitted and graded with
 * the answers saved so far.
 */
class AutoSubmitExpiredExamAttemptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(ExamAttemptService $attempts): void
    {
        $attempts->autoSubmitExpired();
    }
}
