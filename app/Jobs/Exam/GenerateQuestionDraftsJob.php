<?php

namespace App\Jobs\Exam;

use App\Models\Exam\QuestionDraftRun;
use App\Services\Exam\QuestionDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Stage 4: one AI drafting run. Not retried automatically — a retry would
 * pay for the same request twice; the teacher/admin starts a new run instead.
 */
class GenerateQuestionDraftsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public string $runId)
    {
        $this->timeout = (int) config('exam.ai.timeout_seconds', 300) + 60;
    }

    public function handle(QuestionDraftService $service): void
    {
        $run = QuestionDraftRun::query()->find($this->runId);
        if ($run !== null) {
            $service->execute($run);
        }
    }

    public function failed(?Throwable $e): void
    {
        $run = QuestionDraftRun::query()->find($this->runId);
        if ($run !== null && ! $run->isFinished()) {
            $run->markFinished(QuestionDraftRun::STATUS_FAILED, ['error' => 'job failed: '.mb_substr(class_basename($e ?? 'unknown'), 0, 100)]);
        }
    }
}
