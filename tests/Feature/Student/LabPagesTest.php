<?php

namespace Tests\Feature\Student;

use App\Models\LabAttempt;
use App\Models\LabCategory;
use App\Models\LabExperiment;
use Database\Seeders\LabBadgeSeeder;
use Database\Seeders\LabCategorySeeder;
use Database\Seeders\LabExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class LabPagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([LabCategorySeeder::class, LabBadgeSeeder::class, LabExperimentSeeder::class]);
    }

    /** QA-S-07: every category page 500'd (lazy loading of experiment->category) */
    public function test_every_lab_category_page_renders(): void
    {
        $student = $this->studentUser();

        foreach (LabCategory::pluck('slug') as $slug) {
            $this->actingAs($student)->get(route('student.lab.category', $slug))->assertOk();
        }
    }

    public function test_badges_and_progress_pages_render(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)->get(route('student.lab.badges'))->assertOk();
        $this->actingAs($student)->get(route('student.lab.progress'))->assertOk();
    }

    /** QA-S-08: a start time in the future gave a negative (unsigned) time spent → 500 */
    public function test_time_spent_is_never_negative(): void
    {
        $student = $this->studentUser();
        $experiment = LabExperiment::firstOrFail();
        $attempt = LabAttempt::forceCreate([
            'user_id' => $student->id,
            'experiment_id' => $experiment->id,
            'attempt_number' => 1,
            'started_at' => now()->addHours(5),
            'status' => 'in_progress',
            'total_tasks' => 1,
            'max_score' => 100,
        ]);

        $attempt->pause();

        $this->assertSame(0, $attempt->fresh()->time_spent_seconds);
    }
}
