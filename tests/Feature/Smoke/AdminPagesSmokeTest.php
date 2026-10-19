<?php

namespace Tests\Feature\Smoke;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\Olympiad;
use App\Models\OlympiadAnswer;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadQuestion;
use App\Models\OlympiadRegistration;
use App\Models\OlympiadSection;
use App\Models\OlympiadType;
use App\Models\Payment;
use App\Models\QuestionBank;
use App\Models\Review;
use App\Models\SectionAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCourses;
use Tests\Concerns\SmokesRoutes;
use Tests\TestCase;

/**
 * Every admin-panel GET page renders (no 5xx, no missing Vue page).
 */
class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase, CreatesCourses, SmokesRoutes;

    public function test_every_admin_page_renders(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $teacher = $this->teacher();
        $student = $this->studentUser();
        $course = $this->course($teacher);

        $enrollment = Enrollment::forceCreate([
            'user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'completed',
        ]);
        $review = Review::forceCreate([
            'user_id' => $student->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
            'rating' => 4, 'review_text' => 'Yaxshi', 'status' => 'pending',
        ]);
        $payment = Payment::forceCreate([
            'user_id' => $student->id, 'type' => 'course_purchase', 'amount' => 100000, 'status' => 'completed',
        ]);
        $certificate = Certificate::forceCreate([
            'certificate_code' => Str::upper(Str::random(10)),
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'EDU-'.Str::random(6),
            'credential_id' => Str::random(12),
            'issued_at' => now(),
        ]);
        $template = CertificateTemplate::forceCreate([
            'name' => 'Asosiy', 'slug' => 'asosiy', 'pdf_template_path' => 'templates/a.pdf', 'placeholders' => '[]',
        ]);

        $type = OlympiadType::forceCreate([
            'direction_id' => $course->direction_id, 'name' => 'math', 'display_name' => 'Math',
            'display_name_uz' => 'Matematika', 'sections' => '["test"]', 'section_weights' => '{"test":100}',
        ]);
        $olympiad = Olympiad::forceCreate([
            'title' => 'Bahorgi olimpiada', 'slug' => 'bahorgi', 'olympiad_type_id' => $type->id,
            'sections_config' => '[]', 'total_duration_minutes' => 60, 'total_max_points' => 100,
            'status' => 'completed',
        ]);
        $section = OlympiadSection::forceCreate([
            'olympiad_id' => $olympiad->id, 'section_type' => 'test', 'title' => 'Test', 'duration_minutes' => 60,
            'order_number' => 1, 'max_points' => 100, 'weight_percent' => 100,
        ]);
        $bank = QuestionBank::forceCreate(['question_type' => 'single_choice', 'question_text' => '2+2?']);
        $olympiadQuestion = OlympiadQuestion::forceCreate([
            'olympiad_id' => $olympiad->id, 'section_id' => $section->id, 'question_id' => $bank->id,
            'order_number' => 1, 'points' => 5,
        ]);
        $registration = OlympiadRegistration::forceCreate([
            'olympiad_id' => $olympiad->id, 'user_id' => $student->id, 'original_price' => 0, 'final_price' => 0,
        ]);
        $attempt = OlympiadAttempt::forceCreate([
            'olympiad_id' => $olympiad->id, 'user_id' => $student->id, 'registration_id' => $registration->id,
            'session_token' => Str::random(64), 'total_max_score' => 100,
        ]);
        $sectionAttempt = SectionAttempt::forceCreate([
            'attempt_id' => $attempt->id, 'section_id' => $section->id, 'max_score' => 100,
        ]);
        $answer = OlympiadAnswer::forceCreate([
            'attempt_id' => $attempt->id, 'section_attempt_id' => $sectionAttempt->id,
            'olympiad_question_id' => $olympiadQuestion->id, 'question_id' => $bank->id, 'max_points' => 5,
        ]);

        $this->smokeGetRoutes($admin, 'admin.', [
            'user' => $teacher->id,
            'course' => $course->id,
            'review' => $review->id,
            'payment' => $payment->id,
            'certificate' => $certificate->id,
            'template' => $template->id,
            'olympiad' => $olympiad->id,
            'section' => $section->id,
            'question' => $bank->id,
            'attempt' => $attempt->id,
            'answer' => $answer->id,
        ], skip: [
            // file downloads (no generated PDF in tests)
            'admin.certificates.download',
        ]);
    }
}
