<?php

namespace App\Console\Commands\Exam;

use App\Jobs\Exam\GenerateExamVariantsJob;
use App\Models\Exam\ExamSession;
use App\Services\Exam\ExamReadinessChecker;
use App\Services\Exam\VariantGenerator;
use Illuminate\Console\Command;

/**
 *   php artisan exam:generate-variants {session}            (now, in this process)
 *   php artisan exam:generate-variants {session} --queue    (queued job)
 */
class GenerateExamVariantsCommand extends Command
{
    protected $signature = 'exam:generate-variants {session : exam_sessions.id} {--queue : dispatch the job instead of running it here}';

    protected $description = 'Pre-generate the personal variants of an exam session (bank sufficiency is printed first)';

    public function handle(VariantGenerator $generator, ExamReadinessChecker $readiness): int
    {
        $session = ExamSession::query()->find($this->argument('session'));
        if (! $session) {
            $this->error('Session not found.');

            return self::FAILURE;
        }

        $report = $readiness->forSession($session);
        $this->line("Bank: {$report['bank']['status']}, audience: {$report['audience']}, variants: {$report['variants']}");
        foreach ($report['bank']['rules'] as $rule) {
            if ($rule['status'] !== 'ok') {
                $this->warn(sprintf('  %s (qiyinlik %s, %s): kerak %d, bor %d, qo\'shni qiyinlikdan %d',
                    $rule['topic'], $rule['difficulty_level'] ?? 'istalgan', $rule['part'], $rule['required'], $rule['candidates'], $rule['adjacent']));
            }
        }

        if ($this->option('queue')) {
            GenerateExamVariantsJob::dispatch($session->id);
            $this->info('Queued.');

            return self::SUCCESS;
        }

        $summary = $generator->generateForSession($session);
        $this->info(sprintf('created %d, existing %d, with fallback %d, short %d',
            $summary['created'], $summary['existing'], $summary['with_fallback'], $summary['short']));

        return self::SUCCESS;
    }
}
