<?php

namespace App\Console\Commands\Exam;

use App\Jobs\Exam\SendWeeklyGuardianReportsJob;
use App\Services\Exam\ReportBuilder;
use Illuminate\Console\Command;

/**
 * Sunday report to guardians (scheduled in routes/console.php).
 *   php artisan exam:weekly-reports              (this week, Asia/Tashkent)
 *   php artisan exam:weekly-reports --week=2026-10-19
 */
class SendWeeklyReportsCommand extends Command
{
    protected $signature = 'exam:weekly-reports {--week= : any date of the exam week (Y-m-d)}';

    protected $description = 'Build and send the Sunday exam report to guardians (in-app + WeeklyReportReady event)';

    public function handle(ReportBuilder $builder): int
    {
        $week = $this->option('week');
        if ($week !== null && strtotime((string) $week) === false) {
            $this->error('--week must be a date (Y-m-d).');

            return self::INVALID;
        }

        $sent = (new SendWeeklyGuardianReportsJob($week ?: null))->handle($builder);
        $this->info("Hisobotlar yuborildi: {$sent}");

        return self::SUCCESS;
    }
}
