<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\StudentExamProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamOnboardingTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'filled_by' => 'student',
            'grade' => '10',
            'subject' => 'both',
            'goal' => 'milliy_sertifikat',
            'school_name' => '  14-maktab  ',
            'parent_phone' => '+998 90 123 45 67',
            'parent_consent' => true,
            'show_name_publicly' => false,
        ], $overrides);
    }

    public function test_the_form_renders_for_a_student(): void
    {
        $this->actingAs($this->student())
            ->get(route('student.exam.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Exam/Onboarding')->where('profile', null)->where('isComplete', false));
    }

    public function test_parent_consent_is_required(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->post(route('student.exam.onboarding.store'), $this->payload(['parent_consent' => false]))
            ->assertSessionHasErrors('parent_consent');

        $this->assertDatabaseMissing('exam_student_profiles', ['user_id' => $student->id]);
    }

    public function test_parent_phone_must_be_an_uzbek_number(): void
    {
        $this->actingAs($this->student())
            ->post(route('student.exam.onboarding.store'), $this->payload(['parent_phone' => '12345']))
            ->assertSessionHasErrors('parent_phone');
    }

    public function test_saving_stores_the_profile_and_the_consent_time(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->post(route('student.exam.onboarding.store'), $this->payload());

        $target = Route::has('student.exam.index') ? route('student.exam.index') : route('student.exam.progress');
        $response->assertRedirect($target);

        $profile = StudentExamProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertSame(['math', 'physics'], $profile->subjects);
        $this->assertSame(10, $profile->grade);
        $this->assertFalse($profile->is_graduate);
        $this->assertSame('milliy_sertifikat', $profile->goal->value);
        $this->assertSame('14-maktab', $profile->school_name);
        $this->assertSame('+998901234567', $profile->parent_phone);
        $this->assertNotNull($profile->parent_consent_at);
        $this->assertSame('web_student', $profile->parent_consent_source);
        $this->assertFalse($profile->show_name_publicly);
        $this->assertNull($profile->public_name_consent_at);
        $this->assertTrue($profile->isComplete());
    }

    public function test_graduate_and_public_name_consent(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post(route('student.exam.onboarding.store'), $this->payload([
            'filled_by' => 'parent', 'grade' => 'graduate', 'subject' => 'physics', 'show_name_publicly' => true,
        ]));

        $profile = StudentExamProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertNull($profile->grade);
        $this->assertTrue($profile->is_graduate);
        $this->assertSame(['physics'], $profile->subjects);
        $this->assertSame('web_parent', $profile->parent_consent_source);
        $this->assertTrue($profile->show_name_publicly);
        $this->assertNotNull($profile->public_name_consent_at);

        // withdrawing the public name consent clears its timestamp
        $this->actingAs($student)->post(route('student.exam.onboarding.store'), $this->payload(['show_name_publicly' => false]));
        $this->assertNull($profile->fresh()->public_name_consent_at);
    }

    public function test_resaving_keeps_the_first_consent_time(): void
    {
        $student = $this->student();
        $this->travelTo(now()->subDays(3));
        $this->actingAs($student)->post(route('student.exam.onboarding.store'), $this->payload());
        $first = StudentExamProfile::where('user_id', $student->id)->value('parent_consent_at');
        $this->travelBack();

        $this->actingAs($student)->post(route('student.exam.onboarding.store'), $this->payload(['goal' => 'sat']));

        $profile = StudentExamProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('sat', $profile->goal->value);
        $this->assertEquals((string) $first, (string) $profile->getRawOriginal('parent_consent_at'));
    }

    public function test_the_onboarding_gate_redirects_students_without_consent(): void
    {
        Route::middleware(['web', 'auth', 'exam.onboarded'])->get('/_exam-gate-probe', fn () => 'inside');

        $student = $this->student(['parent_consent_at' => null]);
        $this->actingAs($student)->get('/_exam-gate-probe')->assertRedirect(route('student.exam.onboarding'));

        StudentExamProfile::where('user_id', $student->id)->update(['parent_consent_at' => now()]);
        $this->actingAs($student)->get('/_exam-gate-probe')->assertOk()->assertSee('inside');
    }

    public function test_non_students_can_not_open_the_form(): void
    {
        $this->get(route('student.exam.onboarding'))->assertRedirect(route('login'));
        $this->actingAs($this->guardianUser())->get(route('student.exam.onboarding'))->assertForbidden();
    }
}
