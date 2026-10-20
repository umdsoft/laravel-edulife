<?php

namespace Tests\Feature\Smoke;

use App\Models\Battle;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LabCategory;
use App\Models\LabExperiment;
use App\Models\OlympiadSection;
use App\Models\Question;
use App\Models\SupportTicket;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Tournament;
use Database\Seeders\LabCategorySeeder;
use Database\Seeders\LabExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCourses;
use Tests\Concerns\CreatesOlympiads;
use Tests\Concerns\SmokesRoutes;
use Tests\TestCase;

/**
 * Every student GET page renders (no 5xx, no missing Vue page).
 */
class StudentPagesSmokeTest extends TestCase
{
    use RefreshDatabase, CreatesCourses, CreatesOlympiads, SmokesRoutes;

    public function test_every_student_page_renders(): void
    {
        $this->seed([LabCategorySeeder::class, LabExperimentSeeder::class]);

        $teacher = $this->teacher();
        $student = $this->studentUser();
        $course = $this->course($teacher);
        $module = $this->module($course);
        $lesson = $this->lesson($module, ['is_preview' => true, 'is_free' => true]);

        $enrollment = Enrollment::forceCreate([
            'user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'active',
        ]);

        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy', 'questions_count' => 1, 'is_active' => true]);
        $question = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => '2+2']);
        $question->options()->create(['option_text' => '4', 'is_correct' => true]);
        $attempt = TestAttempt::forceCreate([
            'user_id' => $student->id, 'test_id' => $test->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
            'status' => 'submitted', 'started_at' => now()->subMinutes(5), 'submitted_at' => now(), 'expires_at' => now()->addMinutes(25),
            'score' => 100, 'is_passed' => true,
        ]);

        $certificate = Certificate::forceCreate([
            'certificate_code' => Str::upper(Str::random(10)), 'user_id' => $student->id, 'course_id' => $course->id,
            'enrollment_id' => $enrollment->id, 'certificate_number' => 'EDU-'.Str::random(6), 'credential_id' => Str::random(12),
            'issued_at' => now(),
        ]);

        $battle = Battle::forceCreate(['player1_id' => $student->id, 'direction_id' => $course->direction_id]);
        // Telegram WebView: a friend's battle challenge (opened by its invite code)
        $challenge = Battle::forceCreate(['player1_id' => \App\Models\User::factory()->create(['role' => 'student'])->id, 'status' => 'waiting', 'invite_code' => 'Smoke123', 'expires_at' => now()->addMinutes(10)]);
        $tournament = Tournament::forceCreate(['name' => 'Kuzgi turnir', 'slug' => 'kuzgi', 'direction_id' => $course->direction_id]);
        $ticket = SupportTicket::forceCreate([
            'user_id' => $student->id, 'ticket_number' => 'T-'.Str::random(6), 'subject' => 'Savol', 'description' => 'Yordam kerak',
        ]);

        $olympiad = $this->olympiad();
        $section = OlympiadSection::forceCreate([
            'olympiad_id' => $olympiad->id, 'section_type' => 'test', 'title' => 'Test', 'duration_minutes' => 60,
            'order_number' => 1, 'max_points' => 100, 'weight_percent' => 100,
        ]);
        $olympiadAttempt = $this->olympiadAttempt($olympiad, $student);

        $category = LabCategory::firstOrFail();
        $experiment = LabExperiment::firstOrFail();

        $this->smokeGetRoutes($student, 'student.', [
            'course' => $course->id,
            'lesson' => $lesson->id,
            'direction' => $course->direction_id,
            'test' => $test->id,
            'attempt' => $attempt->id,
            'certificate' => $certificate->id,
            'battle' => $battle->id,
            'code' => $challenge->invite_code,
            'tournament' => $tournament->id,
            'ticket' => $ticket->id,
            'user' => $student->id,
            'slug' => $olympiad->slug,
            'section' => $section->id,
            'registration' => $olympiadAttempt->registration_id,
            'student.lab.category' => ['slug' => $category->slug],
            'student.lab.show' => ['slug' => $experiment->slug],
            'student.lab.simulate' => ['slug' => $experiment->slug],
            'student.lab.api.saved.load' => ['id' => (string) Str::uuid7()],
            'report' => (string) Str::uuid7(),
        ], skip: [
            // file downloads
            'student.certificates.download',
            'student.certificates.download-image',
            'student.olympiads.certificate.download',
            'student.lab.api.report.pdf',
        ]);
    }
}
