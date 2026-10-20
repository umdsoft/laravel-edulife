<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- exam: Sunday guardian report (stage 7) ---
\Illuminate\Support\Facades\Schedule::command('exam:weekly-reports')
    ->weeklyOn((int) config('exam.report.weekly_day', 0), (string) config('exam.report.weekly_time', '09:00'))
    ->timezone('Asia/Tashkent')
    ->withoutOverlapping()
    ->name('exam-weekly-guardian-reports');
// --- end exam: stage 7 ---
