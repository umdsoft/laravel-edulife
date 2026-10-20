<?php

namespace App\Console\Commands\Exam;

use App\Services\Exam\ExamSessionLifecycle;
use Illuminate\Console\Command;

/**
 * Exam scheduler pass (routes/console.php, every minute):
 * pre-generate variants, open / close sessions by schedule, auto-submit
 * expired attempts.
 */
class ExamTickCommand extends Command
{
    protected $signature = 'exam:tick';

    protected $description = 'Open/close exam sessions by schedule, pre-generate variants, auto-submit expired attempts';

    public function handle(ExamSessionLifecycle $lifecycle): int
    {
        $stats = $lifecycle->tick();
        $this->line(sprintf(
            'variants queued: %d, opened: %d, closed: %d, auto-submitted: %d',
            $stats['generating'], $stats['opened'], $stats['closed'], $stats['auto_submitted']
        ));

        return self::SUCCESS;
    }
}
