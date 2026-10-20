<?php

namespace App\Services\Telegram;

use App\Models\Exam\ExamAttempt;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramNotification;
use Illuminate\Support\Facades\Route;

/**
 * Telegram messages for exam events (stage 6 listeners):
 *  - a graded attempt -> the student and their approved guardians;
 *  - the Sunday report (WeeklyReportReady, stage 7) -> the guardian.
 * Every method returns the number of messages sent; nothing is sent while
 * the bot is not configured.
 */
class ExamTelegramNotifier
{
    public function __construct(private readonly TelegramMessenger $messenger) {}

    public function attemptGraded(ExamAttempt $attempt): int
    {
        if (! $this->messenger->isConfigured()) {
            return 0;
        }

        $attempt->loadMissing(['session:id,title,type', 'user:id,first_name,last_name']);
        $vars = [
            'title' => $attempt->session?->title ?? '—',
            'score' => BotUpdateHandler::number($attempt->score),
            'max' => BotUpdateHandler::number($attempt->max_score),
            'percent' => BotUpdateHandler::number($attempt->score_percent),
        ];
        $resultButton = Route::has('student.exam.result')
            ? BotText::inline([[BotText::appButton('open_result', 'result_'.$attempt->id)]])
            : BotText::inline([[BotText::appButton('open_app', 'exam')]]);

        $sent = 0;

        $student = TelegramAccount::query()->where('user_id', $attempt->user_id)->first();
        if ($student !== null) {
            $lines = [BotText::get('attempt.student', $vars)];
            $delta = StudentWeeklyResult::query()->where('attempt_id', $attempt->id)->value('delta_prev');
            if ($delta !== null && (float) $delta != 0.0) {
                $lines[] = (float) $delta > 0
                    ? BotText::get('attempt.student_delta_up', ['delta' => BotUpdateHandler::number($delta)])
                    : BotText::get('attempt.student_delta_down', ['delta' => BotUpdateHandler::number($delta)]);
            }
            $lines[] = BotText::get('attempt.student_videos');
            $sent += (int) $this->messenger->notifyOnce($student, TelegramNotification::KIND_ATTEMPT_RESULT, $attempt->id, implode("\n", $lines), $resultButton);
        }

        $guardianIds = GuardianStudent::query()
            ->where('student_id', $attempt->user_id)
            ->whereNotNull('verified_at')
            ->whereNull('rejected_at')
            ->where('receives_reports', true)
            ->pluck('guardian_id');
        if ($guardianIds->isNotEmpty()) {
            $text = BotText::get('attempt.guardian', $vars + ['name' => $attempt->user?->first_name ?? '']);
            // TW: the guardian's copy opens that child's page in the Mini App
            $childButton = BotText::inline([[BotText::appButton('open_result', 'child_'.$attempt->user_id)]]);
            foreach (TelegramAccount::query()->whereIn('user_id', $guardianIds)->get() as $account) {
                $sent += (int) $this->messenger->notifyOnce($account, TelegramNotification::KIND_GUARDIAN_RESULT, $attempt->id, $text, $childButton);
            }
        }

        return $sent;
    }

    /**
     * $report = ReportBuilder::forStudent() shape (see App\Events\Exam\WeeklyReportReady):
     * week_start, student{first_name}, subjects[{name, percent, delta, ...}], recommendations[{name}], report_id, url.
     */
    public function weeklyReport(GuardianStudent $link, array $report): int
    {
        if (! $this->messenger->isConfigured()) {
            return 0;
        }
        if ($link->verified_at === null || $link->rejected_at !== null || ! $link->receives_reports) {
            return 0;
        }

        $account = TelegramAccount::query()->where('user_id', $link->guardian_id)->first();
        if ($account === null) {
            return 0;
        }

        $lines = [BotText::get('report.title', [
            'name' => $report['student']['first_name'] ?? '',
            'week' => (string) ($report['week_start'] ?? ''),
        ])];
        foreach ((array) ($report['subjects'] ?? []) as $subject) {
            $name = $subject['name'] ?? ($subject['code'] ?? '');
            if (! isset($subject['percent']) || $subject['percent'] === null) {
                $lines[] = BotText::get('report.no_result', ['subject' => $name]);

                continue;
            }
            $delta = $subject['delta'] ?? null;
            $lines[] = BotText::get('report.subject_line', [
                'subject' => $name,
                'percent' => BotUpdateHandler::number($subject['percent']),
                'delta' => '',
            ]).($delta !== null && (float) $delta != 0.0 ? BotText::get('report.delta', [
                'sign' => (float) $delta > 0 ? '+' : '',
                'delta' => BotUpdateHandler::number($delta),
            ]) : '');
        }

        $recommendations = array_slice((array) ($report['recommendations'] ?? []), 0, 3);
        if ($recommendations !== []) {
            $lines[] = '';
            $lines[] = BotText::get('report.recommend_title');
            foreach ($recommendations as $topic) {
                $lines[] = BotText::get('report.recommend_line', ['topic' => is_array($topic) ? ($topic['name'] ?? '') : (string) $topic]);
            }
        }

        $url = $report['url'] ?? null;
        // Telegram WebView: the report opens inside the Mini App (web_app button, guardian login by initData)
        $reportId = $report['report_id'] ?? null;
        $markup = is_string($url) && str_starts_with($url, 'https://')
            ? BotText::inline([[BotText::appButton('open_report', is_string($reportId) && $reportId !== '' ? 'report_'.$reportId : 'report')]])
            : null;

        $ref = (string) ($report['report_id'] ?? ($link->id.':'.($report['week_start'] ?? '')));

        return (int) $this->messenger->notifyOnce($account, TelegramNotification::KIND_WEEKLY_REPORT, mb_substr($ref, 0, 64), implode("\n", $lines), $markup);
    }
}
