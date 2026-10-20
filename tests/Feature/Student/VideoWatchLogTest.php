<?php

namespace Tests\Feature\Student;

use App\Http\Controllers\Student\LessonProgressController;
use App\Models\DailyMission;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\UserMission;
use App\Models\VideoWatchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * The "30 daqiqa video tomosha qiling" mission never moved: the player never
 * called video-log. The endpoint now credits only real, capped watch time.
 */
class VideoWatchLogTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private User $student;
    private Lesson $lesson;
    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfDay()->addHours(10));

        DailyMission::forceCreate(['title' => '30 daqiqa video tomosha qiling', 'description' => 'Video', 'type' => 'watch_time', 'code' => 'watch_time', 'target_count' => 1800, 'xp_reward' => 10, 'coin_reward' => 2, 'is_active' => true]);

        $course = $this->course($this->teacher());
        $module = $this->module($course);
        $this->lesson = $this->lesson($module, ['type' => 'video', 'video_duration' => 600]);
        $this->student = $this->studentUser();
        $this->enrollment = Enrollment::forceCreate(['user_id' => $this->student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        $this->progressFor($this->lesson);
    }

    private function progressFor(Lesson $lesson): LessonProgress
    {
        return LessonProgress::forceCreate([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $lesson->course_id,
            'total_duration' => 600,
        ]);
    }

    private function log(array $payload, ?Lesson $lesson = null)
    {
        return $this->actingAs($this->student)->postJson(
            route('student.learn.progress.video-log', $lesson ?? $this->lesson),
            array_merge(['session_id' => 'sess-1', 'seq' => 1, 'duration' => 30, 'start_position' => 0, 'end_position' => 30, 'playback_rate' => 1], $payload)
        );
    }

    private function missionSeconds(): int
    {
        return (int) UserMission::where('user_id', $this->student->id)->value('current_value');
    }

    public function test_a_chunk_credits_the_lesson_profile_enrollment_and_watch_time_mission(): void
    {
        $this->log([])->assertOk()->assertJson(['credited' => 30, 'watch_time' => 30]);

        $progress = LessonProgress::where('lesson_id', $this->lesson->id)->first();
        $this->assertSame(30, $progress->watch_time);
        $this->assertSame(30, $progress->last_position);
        $this->assertSame(30, (int) $this->student->studentProfile()->first()->total_watch_time);
        $this->assertSame(30, (int) $this->enrollment->fresh()->total_watch_time);
        $this->assertSame(30, $this->missionSeconds());
        $this->assertSame(1, VideoWatchLog::count());
    }

    public function test_a_request_may_not_report_more_than_the_per_request_maximum(): void
    {
        $this->log(['duration' => LessonProgressController::MAX_SECONDS_PER_REQUEST + 1])->assertStatus(422);
        $this->log(['session_id' => null])->assertStatus(422);
        $this->log(['duration' => 0])->assertStatus(422);
        $this->assertSame(0, VideoWatchLog::count());
    }

    public function test_credited_time_never_exceeds_the_real_elapsed_time(): void
    {
        $this->log(['seq' => 1])->assertJson(['credited' => 30]);

        // a second chunk right away: only the clock tolerance is credited
        $this->log(['seq' => 2])->assertJson(['credited' => LessonProgressController::CLOCK_TOLERANCE_SECONDS]);

        // 10 real seconds later another "30 s" chunk is capped at 10 s (+ tolerance)
        $this->travel(10)->seconds();
        $this->log(['seq' => 3])->assertJson(['credited' => 10 + LessonProgressController::CLOCK_TOLERANCE_SECONDS]);

        // an honest cadence is credited in full
        $this->travel(30)->seconds();
        $this->log(['seq' => 4])->assertJson(['credited' => 30]);

        $this->assertSame(30 + 2 + 12 + 30, $this->missionSeconds());
    }

    public function test_two_tabs_or_lessons_share_one_clock(): void
    {
        $other = $this->lesson($this->module($this->lesson->course), ['type' => 'video', 'sort_order' => 2]);
        $this->progressFor($other);

        $this->log(['session_id' => 'tab-a'])->assertJson(['credited' => 30]);
        $this->travel(15)->seconds();
        $this->log(['session_id' => 'tab-b'], $other)->assertJson(['credited' => 15 + LessonProgressController::CLOCK_TOLERANCE_SECONDS]);
    }

    public function test_a_retried_chunk_is_counted_once(): void
    {
        $this->log(['seq' => 7])->assertJson(['credited' => 30]);
        $this->travel(60)->seconds();
        $this->log(['seq' => 7])->assertOk()->assertJson(['credited' => 0, 'duplicate' => true]);

        $this->assertSame(30, LessonProgress::where('lesson_id', $this->lesson->id)->value('watch_time'));
        $this->assertSame(1, VideoWatchLog::count());
    }

    public function test_a_lesson_the_student_never_opened_is_rejected(): void
    {
        $other = $this->lesson($this->module($this->lesson->course), ['type' => 'video', 'sort_order' => 2]);

        $this->log([], $other)->assertNotFound();
        $this->assertSame(0, VideoWatchLog::count());
    }

    public function test_positions_are_clamped_to_the_video_length(): void
    {
        $this->log(['start_position' => 590, 'end_position' => 9999])->assertOk();

        $log = VideoWatchLog::first();
        $this->assertSame(590, $log->start_position);
        $this->assertSame(600, $log->end_position);
    }

    public function test_thirty_minutes_of_playback_completes_the_daily_mission(): void
    {
        for ($seq = 1; $seq <= 15; $seq++) {
            $this->log(['seq' => $seq, 'duration' => 120])->assertJson(['credited' => 120]);
            $this->travel(120)->seconds();
        }

        $mission = UserMission::where('user_id', $this->student->id)->with('mission')->first();
        $this->assertTrue($mission->is_completed);
        $this->assertSame('30 / 30 daqiqa', $mission->progress_label);
        $this->assertSame(1800, (int) $this->student->studentProfile()->first()->total_watch_time);
    }
}
