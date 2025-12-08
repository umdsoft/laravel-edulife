<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// English Services
use App\Services\English\LevelService;
use App\Services\English\TopicService;
use App\Services\English\UnitService;
use App\Services\English\LessonService;
use App\Services\English\VocabularyService;
use App\Services\English\GrammarService;
use App\Services\English\GameService;
use App\Services\English\BattleService;
use App\Services\English\AIConversationService;
use App\Services\English\AchievementService;
use App\Services\English\LeaderboardService;
use App\Services\English\NotificationService;

class EnglishServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Singleton services for shared state
        $this->app->singleton(LevelService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(LeaderboardService::class);

        // Regular services
        $this->app->bind(TopicService::class);
        $this->app->bind(UnitService::class);
        $this->app->bind(LessonService::class);
        $this->app->bind(VocabularyService::class);
        $this->app->bind(GrammarService::class);
        $this->app->bind(GameService::class);
        $this->app->bind(BattleService::class);
        $this->app->bind(AIConversationService::class);
        $this->app->bind(AchievementService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
