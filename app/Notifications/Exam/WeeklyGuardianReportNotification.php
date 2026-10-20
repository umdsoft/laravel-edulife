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
            $delta = $s['delta'] !== null && $s['delta'] != 0 ? ' ('.($s['delta'] > 0 ? '+' : '').self::num($s['delta']).')' : '';
            $parts[] = "{$s['name']}: ".self::num($s['percent'])."%{$delta}";
        }
        $body = $parts !== []
            ? implode(', ', $parts)
            : 'Bu hafta imtihon topshirilmadi';
        $topics = array_column($p['recommendations'] ?? [], 'name');
        if ($topics !== []) {
            $body .= '. Keyingi hafta: '.implode(', ', $topics);
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

    /** 71.1 -> "71,1" (Uzbek decimal comma) */
    private static function num(float|int|null $value): string
    {
        return str_replace('.', ',', (string) round((float) $value, 1));
    }

    public function toArray($notifiable): array
    {
        return $this->toApp($notifiable);
    }
}
