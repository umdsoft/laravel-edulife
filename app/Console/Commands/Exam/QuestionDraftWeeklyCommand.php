<?php

namespace App\Console\Commands\Exam;

use App\Services\Exam\QuestionDraftWeeklyPlanner;
use Illuminate\Console\Command;

/**
 * Stage 4: weekly AI drafting (scheduled in routes/console.php).
 * Does nothing while ANTHROPIC_API_KEY is empty.
 */
class QuestionDraftWeeklyCommand extends Command
{
    protected $signature = 'exam:ai-drafts-weekly';

    protected $description = 'Queue this week\'s AI question drafts from the teacher\'s source documents';

    public function handle(QuestionDraftWeeklyPlanner $planner): int
    {
        $runs = $planner->run();
        $this->info(count($runs).' AI draft run(s) started.');

        return self::SUCCESS;
    }
}
