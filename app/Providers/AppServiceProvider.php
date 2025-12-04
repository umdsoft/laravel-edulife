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
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        
        // Prevent lazy loading in development (N+1 detection)
        Model::preventLazyLoading(!$this->app->isProduction());
        
        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());
    }
}
