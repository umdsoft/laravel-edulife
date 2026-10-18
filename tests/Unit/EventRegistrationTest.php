<?php

namespace Tests\Unit;

use App\Events\CourseCompleted;
use App\Events\LessonCompleted;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    public function test_xp_listeners_are_registered_exactly_once(): void
    {
        // A duplicate registration awards XP twice and issues two certificates
        $this->assertCount(1, Event::getListeners(LessonCompleted::class));
        $this->assertCount(1, Event::getListeners(CourseCompleted::class));
    }
}
