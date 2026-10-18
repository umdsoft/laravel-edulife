<?php

namespace Tests\Feature\Student;

use App\Services\CourseRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_interests_are_bound_not_interpolated(): void
    {
        $preferred = $this->course($this->teacher(), ['title' => 'Preferred']);
        $this->course($this->teacher(), ['title' => 'Other']);

        $student = $this->studentUser();
        // A quote in stored interests must not break (or inject into) the SQL
        $student->studentProfile()->update(['interests' => [$preferred->direction_id, "x') OR 1=1 --"]]);

        $result = app(CourseRecommendationService::class)->getRecommendations($student->fresh());

        $this->assertCount(2, $result);
        $this->assertSame('Preferred', $result->first()->title);
    }
}
