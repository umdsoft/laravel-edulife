<?php

namespace App\Jobs\Exam;

use App\Models\QuestionBank;
use App\Services\Exam\GradingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Re-grades the finished attempts that answered a question after it was
 * retired or re-approved with a (possibly) new answer key — the queued form
 * of `php artisan exam:regrade {question}` (spec section 7).
 */
class RegradeQuestionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $uniqueFor = 300;

    public function __construct(public string $questionId) {}

    public function uniqueId(): string
    {
        return $this->questionId;
    }

    public function handle(GradingService $grading): void
    {
        $question = QuestionBank::withTrashed()->find($this->questionId);
        if (! $question) {
            return;
        }

        $result = $grading->regradeQuestion($question);
        Log::info('exam: question regraded', ['question_id' => $question->id] + $result);
    }
}
