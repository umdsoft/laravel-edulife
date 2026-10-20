<?php

namespace App\Notifications\Exam;

use App\Models\Exam\GuardianReport;
use App\Notifications\Channels\AppDatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

/**
 * In-app (bell) copy of the Sunday report for a guardian.
 */
class WeeklyGuardianReportNotification extends Notification
{
    public function __construct(public GuardianReport $report) {}

    public function via($notifiable): array
    {
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable): array
    {
        $p = $this->report->payload;
        $name = $p['student']['first_name'] ?? 'Farzandingiz';

        $parts = [];
        foreach ($p['subjects'] ?? [] as $s) {
            if (! $s['participated']) {
                continue;
            }
            $delta = $s['delta'] !== null && $s['delta'] != 0 ? ' ('.($s['delta'] > 0 ? '+' : '').$s['delta'].')' : '';
            $parts[] = "{$s['name']}: {$s['percent']}%{$delta}";
        }
        $body = $parts !== []
            ? implode(', ', $parts)
            : 'Bu hafta imtihon topshirilmadi.';
        $topics = array_column($p['recommendations'] ?? [], 'name');
        if ($topics !== []) {
            $body .= '. Keyingi hafta: '.implode(', ', $topics).'.';
        }

        return [
            'type' => 'exam_weekly_report',
            'title' => "{$name}ning haftalik hisoboti",
            'body' => mb_substr($body, 0, 500),
            'action_url' => Route::has('guardian.reports.show') ? route('guardian.reports.show', $this->report, false) : null,
            'icon' => 'chart',
            'data' => [
                'report_id' => $this->report->id,
                'week_start' => $p['week_start'] ?? null,
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toApp($notifiable);
    }
}
