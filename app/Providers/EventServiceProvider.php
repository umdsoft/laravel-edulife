<?php

namespace App\Providers;

use App\Events\LessonCompleted;
use App\Events\CourseCompleted;
use App\Listeners\AwardLessonXP;
use App\Listeners\AwardCourseXP;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        LessonCompleted::class => [
            AwardLessonXP::class,
        ],
        CourseCompleted::class => [
            AwardCourseXP::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
