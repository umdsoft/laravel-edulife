<?php

namespace Tests\Feature\Student;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\DailyMission;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\UserAchievement;
use App\Models\UserMission;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA2-S-02: lesson and test daily missions stayed 0/1 and no achievement ever
 * unlocked: nothing told the mission service about lessons/tests, and the
 * seeded requirements ({"lessons": 1}) were read as {"type", "value"}.
 */
class GamificationProgressTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function missions(): void
    {
        foreach (['complete_lessons' => '1 ta dars tugating', 'pass_tests' => "1 ta testdan o'ting", 'win_battles' => '1 ta battle yutib oling'] as $type => $title) {
            DailyMission::forceCreate(['title' => $title, 'description' => $title, 'type' => $type, 'code' => $type, 'target_count' => 1, 'xp_reward' => 5, 'coin_reward' => 2, 'is_active' => true]);
        }
    }

    public function test_completing_a_lesson_and_passing_a_test_move_missions_and_unlock_achievements(): void
    {
        $this->seed(AchievementSeeder::class);
        $this->missions();

        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course), ['type' => 'text']);
        $this->lesson($this->module($course), ['type' => 'text', 'title' => 'Ikkinchi dars', 'sort_order' => 2]);
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'active', 'progress' => 100]);

        // the student has not opened the dashboard today: the lesson still counts
        $this->actingAs($student)->get(route('student.learn.lesson', [$course, $lesson]))->assertOk();
        $this->actingAs($student)->postJson(route('student.learn.progress.complete', $lesson))->assertOk();

        $lessonMission = UserMission::where('user_id', $student->id)
            ->whereHas('mission', fn ($q) => $q->where('type', 'complete_lessons'))->firstOrFail();
        $this->assertSame(1, $lessonMission->current_value);
        $this->assertTrue($lessonMission->is_completed);

        $firstStep = Achievement::where('title', 'Birinchi qadam')->firstOrFail();
        $this->assertTrue(UserAchievement::where('user_id', $student->id)->where('achievement_id', $firstStep->id)->exists());

        // pass a test
        $test = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'final', 'testable_type' => Course::class, 'testable_id' => $course->id,
            'title' => 'Yakuniy test', 'questions_count' => 1, 'time_limit' => 30, 'passing_score' => 60,
            'max_attempts' => 3, 'shuffle_questions' => false, 'is_active' => true,
        ]);
        $question = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'Savol', 'points' => 1, 'sort_order' => 1]);
        $correct = QuestionOption::forceCreate(['question_id' => $question->id, 'option_text' => "To'g'ri", 'is_correct' => true, 'sort_order' => 1]);

        Enrollment::where('user_id', $student->id)->update(['progress' => 100]); // the final test opens at 80%
        $this->actingAs($student)->post(route('student.tests.begin', $test), ['confirm' => true])->assertRedirect();
        $attempt = TestAttempt::where('user_id', $student->id)->firstOrFail();
        $this->actingAs($student)->postJson(route('student.tests.save-answer', $attempt), ['question_id' => $question->id, 'answer' => $correct->id])->assertOk();
        $this->actingAs($student)->postJson(route('student.tests.submit', $attempt), ['confirm' => true])->assertOk();

        $testMission = UserMission::where('user_id', $student->id)
            ->whereHas('mission', fn ($q) => $q->where('type', 'pass_tests'))->firstOrFail();
        $this->assertTrue($testMission->is_completed);

        $unlocked = UserAchievement::where('user_id', $student->id)->with('achievement')->get()->pluck('achievement.title');
        $this->assertContains('Imtihonchi', $unlocked);
        $this->assertContains('Mukammal', $unlocked); // 100%
        $this->assertNotContains('Bilimdon', $unlocked); // 50 lessons

        // the achievements page lists them as unlocked and the reward reaches the profile
        $this->actingAs($student)->get(route('student.achievements.index'))
            ->assertInertia(fn (Assert $p) => $p->where('total_unlocked', 3));

        $before = (int) $student->studentProfile()->first()->coins;
        $this->actingAs($student)->postJson(route('student.achievements.claim', $firstStep))->assertOk()->assertJsonPath('success', true);
        $this->assertSame($before + (int) $firstStep->coin_reward, (int) $student->studentProfile()->first()->coins);
    }

    public function test_the_achievements_page_catches_up_on_earlier_progress(): void
    {
        $this->seed(AchievementSeeder::class);
        $student = $this->studentUser();
        $student->studentProfile->update(['lessons_completed' => 12, 'courses_completed' => 1, 'battles_won' => 1]);

        $this->actingAs($student)->get(route('student.achievements.index'))
            ->assertInertia(fn (Assert $p) => $p->where('achievements', fn ($list) => collect($list)
                ->where('is_unlocked', true)->pluck('title')->sort()->values()->all()
                    === collect(['Birinchi qadam', "O'quvchi", 'Kurs tugadi', 'Birinchi qon'])->sort()->values()->all()));
    }
}
