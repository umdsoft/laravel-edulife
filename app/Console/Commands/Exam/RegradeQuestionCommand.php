<?php

namespace App\Console\Commands\Exam;

use App\Models\QuestionBank;
use App\Services\Exam\GradingService;
use Illuminate\Console\Command;

/**
 * After a question was fixed (new answer key) or retired:
 *   php artisan exam:regrade {question}
 * re-grades every finished attempt that contains it with the current key
 * (a retired question is voided: 0 of 0 points) and refreshes topic mastery
 * and weekly results (delta, rank).
 */
class RegradeQuestionCommand extends Command
{
    protected $signature = 'exam:regrade {question : question_bank.id}';

    protected $description = 'Re-grade every exam attempt that contains a fixed or retired question';

    public function handle(GradingService $grading): int
    {
        $question = QuestionBank::withTrashed()->find($this->argument('question'));
        if (! $question) {
            $this->error('Question not found.');

            return self::FAILURE;
        }

        $result = $grading->regradeQuestion($question);
        $this->info("Qayta baholandi: {$result['attempts']} ta urinish, natijasi o'zgargan: {$result['changed']} ta.");

        return self::SUCCESS;
    }
}
