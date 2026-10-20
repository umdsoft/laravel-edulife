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

// --- exam: sessions by schedule, variant pre-generation, auto-submit (stage 5) ---
\Illuminate\Support\Facades\Schedule::command('exam:tick')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('exam-engine-tick');
// --- end exam: stage 5 ---
