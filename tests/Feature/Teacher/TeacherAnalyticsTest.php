<?php

namespace Tests\Feature\Teacher;

use App\Http\Controllers\Teacher\AnalyticsController;
use App\Models\CourseView;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherAnalyticsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_referers_are_bucketed_into_traffic_sources(): void
    {
        $this->assertSame('direct', AnalyticsController::classifyReferer(null, 'edulife.uz'));
        $this->assertSame('internal', AnalyticsController::classifyReferer('https://edulife.uz/student/courses', 'edulife.uz'));
        $this->assertSame('search', AnalyticsController::classifyReferer('https://www.google.co.uz/search?q=x', 'edulife.uz'));
        $this->assertSame('search', AnalyticsController::classifyReferer('https://yandex.uz/', 'edulife.uz'));
        $this->assertSame('social', AnalyticsController::classifyReferer('https://t.me/edulife', 'edulife.uz'));
        $this->assertSame('social', AnalyticsController::classifyReferer('https://m.youtube.com/watch', 'edulife.uz'));
        $this->assertSame('other', AnalyticsController::classifyReferer('https://blog.example.com/post', 'edulife.uz'));
    }

    public function test_traffic_sources_come_from_recorded_course_views(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        foreach ([null, null, 'https://t.me/x', 'https://www.google.com/'] as $referer) {
            CourseView::forceCreate(['course_id' => $course->id, 'referer' => $referer, 'viewed_date' => now()]);
        }
        // another teacher's views are not counted
        CourseView::forceCreate(['course_id' => $this->course($this->teacher())->id, 'referer' => null, 'viewed_date' => now()]);

        $this->actingAs($teacher)->get(route('teacher.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('trafficSources', 3)
                ->where('trafficSources.0.key', 'direct')
                ->where('trafficSources.0.count', 2)
                ->where('trafficSources.0.percent', 50));
    }

    public function test_no_views_means_no_traffic_sources(): void
    {
        $teacher = $this->teacher();
        $this->course($teacher);

        $this->actingAs($teacher)->get(route('teacher.analytics.index'))
            ->assertInertia(fn (Assert $page) => $page->where('trafficSources', []));
    }

    public function test_export_downloads_a_csv_of_the_teachers_courses(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['title' => 'Algebra 7']);
        $this->course($teacher, ['title' => 'Qoralama', 'status' => 'draft']);
        $this->course($this->teacher(), ['title' => 'Begona kurs']);
        Enrollment::forceCreate(['user_id' => $this->studentUser()->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'completed']);

        $response = $this->actingAs($teacher)->get(route('teacher.analytics.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"Algebra 7";"Nashr qilingan";1;0;1;100', $csv);
        $this->assertStringContainsString('Qoralama;Qoralama;0', $csv);
        $this->assertStringNotContainsString('Begona kurs', $csv);
    }
}
