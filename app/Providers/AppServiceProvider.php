<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // --- Telegram WebView: a separate local test bundle, so Playwright runs can use fresh assets
        // without touching public/build (VITE_BUILD_DIRECTORY=build-t php -S ... ; local only) ---
        if ($this->app->environment('local') && is_string($dir = env('VITE_BUILD_DIRECTORY')) && preg_match('/^build-[a-z0-9]+$/', $dir)) {
            \Illuminate\Support\Facades\Vite::useBuildDirectory($dir);
        }
        // --- end Telegram WebView ---

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        
        // Prevent lazy loading in development (N+1 detection)
        Model::preventLazyLoading(!$this->app->isProduction());
        
        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());

        // Admin "Tizim loglari": record sign-ins
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            // auth (agent L): how they signed in (Telegram code / approval / deep link), set by TelegramLoginService
            $method = request()->attributes->get('login_method');
            $via = ['telegram_code' => ' (Telegram kod)', 'telegram_approve' => ' (Telegram tasdiq)', 'telegram_link' => ' (Telegram havola)'][$method] ?? '';
            \App\Models\AuditLog::log('login', $event->user, [], $method ? ['method' => $method] : [], 'Tizimga kirdi: '.($event->user->full_name ?? '').$via, (string) $event->user->getAuthIdentifier());
        });

        // --- exam: referral funnel + leaderboard cache (stage 7) ---
        \Illuminate\Support\Facades\Event::listen(\App\Events\Exam\ExamAttemptGraded::class, \App\Listeners\Exam\AdvanceReferralOnPlacement::class);
        \App\Models\Subscription::saved(fn (\App\Models\Subscription $s) => app(\App\Listeners\Exam\AdvanceReferralOnSubscription::class)($s));
        \App\Models\Exam\StudentExamProfile::saved(function (\App\Models\Exam\StudentExamProfile $p) {
            if ($p->wasChanged(['show_name_publicly', 'public_alias', 'school_id', 'school_name'])) {
                \App\Services\Exam\LeaderboardService::flush();
            }
        });
        // --- end exam: stage 7 ---

        // --- exam: Telegram delivery of exam events (stage 6) ---
        // Listeners send only while TELEGRAM_BOT_TOKEN is set; events are
        // owned by stages 5 and 7, hence the class_exists guards.
        foreach ([
            'App\\Events\\Exam\\ExamAttemptGraded' => \App\Listeners\Telegram\SendAttemptResultToTelegram::class,
            'App\\Events\\Exam\\ExamSessionOpened' => \App\Listeners\Telegram\SendSessionStartToTelegram::class,
            'App\\Events\\Exam\\WeeklyReportReady' => \App\Listeners\Telegram\SendWeeklyReportToTelegram::class,
        ] as $event => $listener) {
            if (class_exists($event)) {
                \Illuminate\Support\Facades\Event::listen($event, $listener);
            }
        }
        // --- end exam: stage 6 ---

        // --- TW (Telegram WebView-first): rate limits per Telegram user, not per (CGNAT) IP ---
        \App\Services\Telegram\TelegramRateLimits::register();
        // --- end TW ---

        // --- auth: website login through the Telegram bot (agent L) ---
        \App\Services\Auth\TelegramLoginRateLimits::register();
        // --- end auth ---
    }
}
