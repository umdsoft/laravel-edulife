<?php

namespace App\Events\Exam;

use App\Models\Exam\GuardianStudent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The Sunday report for one guardian about one child is built and stored
 * (exam_guardian_reports) and the in-app notification was created.
 * Telegram delivery (stage 6) listens to this.
 *
 * $report = App\Services\Exam\ReportBuilder::forStudent() shape:
 *   week_start, student {first_name}, subjects[{code, name, score, max_score, percent, delta, rank,
 *   participants, topics[{code, name, score}]}], recommendations[{code, name, subject, score}],
 *   report_id, url
 */
class WeeklyReportReady
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GuardianStudent $link,
        public array $report,
    ) {}
}
