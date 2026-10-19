<?php

namespace Tests\Feature\Admin;

use App\Models\Direction;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class AdminListingPagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_reports_page_groups_courses_by_direction(): void
    {
        $this->course($this->teacher());

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports/Index')
                ->where('charts.courses.labels', ['Dasturlash'])
                ->where('charts.courses.data', [1]));
    }

    public function test_leaderboard_lists_students_by_profile_xp(): void
    {
        $low = $this->studentUser();
        $high = $this->studentUser();
        $low->studentProfile()->update(['xp' => 50]);
        $high->studentProfile()->update(['xp' => 900]);
        $this->teacher(); // not a student: must not appear

        $this->actingAs($this->admin())
            ->get(route('admin.leaderboard.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Leaderboard/Config')
                ->has('topUsers', 2)
                ->where('topUsers.0.id', $high->id)
                ->where('topUsers.0.xp', 900)
                ->where('topUsers.1.id', $low->id)
                ->etc());
    }

    public function test_questions_page_loads_test_and_course(): void
    {
        $course = $this->course($this->teacher());
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy test']);
        Question::forceCreate([
            'course_id' => $course->id,
            'test_id' => $test->id,
            'type' => 'single_choice',
            'question_text' => 'Laravel nima?',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.questions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions/Index')
                ->where('questions.data.0.test.title', 'Yakuniy test')
                ->where('questions.data.0.course.title', $course->title));
    }

    public function test_question_routes_point_to_existing_actions(): void
    {
        // questions has no moderation column: approve/reject never existed
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.questions.approve'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.questions.reject'));

        // details open in a modal on the list; the separate show page never existed
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.questions.show'));

        foreach (['admin.questions.index', 'admin.questions.destroy'] as $name) {
            [$controller, $method] = explode('@', \Illuminate\Support\Facades\Route::getRoutes()->getByName($name)->getActionName());
            $this->assertTrue(method_exists($controller, $method), "{$name} -> {$controller}@{$method}");
        }

        $course = $this->course($this->teacher());
        $question = Question::forceCreate([
            'course_id' => $course->id,
            'type' => 'single_choice',
            'question_text' => 'O\'chiriladigan savol',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.questions.destroy', $question))
            ->assertSessionHas('success');
        $this->assertSoftDeleted($question);
    }

    public function test_certificate_template_forms_list_active_directions(): void
    {
        Direction::forceCreate(['name_uz' => 'Faol', 'slug' => 'faol', 'is_active' => true]);
        Direction::forceCreate(['name_uz' => 'Nofaol', 'slug' => 'nofaol', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.certificate-templates.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Certificates/Templates/Create')
                ->has('directions', 1)
                ->where('directions.0.slug', 'faol'));
    }

    public function test_payments_can_be_searched_by_provider_transaction_id(): void
    {
        $student = $this->studentUser();
        $payment = Payment::forceCreate([
            'user_id' => $student->id,
            'type' => 'course_purchase',
            'amount' => 100000,
            'status' => 'completed',
            'provider' => 'payme',
            'provider_transaction_id' => 'PAYME-778899',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index', ['search' => '778899']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Payments/Index')
                ->has('payments.data', 1)
                ->where('payments.data.0.transaction_id', 'PAYME-778899'));

        $this->actingAs($this->admin())
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.transaction_id', 'PAYME-778899')
                ->where('payment.payment_method', 'payme'));
    }

    public function test_review_search_matches_text_and_respects_status_filter(): void
    {
        $teacher = $this->teacher();
        $student = $this->studentUser();

        // reviews are unique per (user, course), so each review gets its own course
        $make = function (string $text, string $status) use ($teacher, $student) {
            $course = $this->course($teacher);
            $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

            return Review::forceCreate([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'rating' => 5,
                'review_text' => $text,
                'status' => $status,
            ]);
        };

        $pending = $make('Juda zo\'r kurs', 'pending');
        $make('Yomon emas', 'approved');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.reviews.index', ['search' => 'zo\'r']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.id', $pending->id));

        // Search hits both reviews via the author name; status filter must still apply
        $this->actingAs($admin)
            ->get(route('admin.reviews.index', ['search' => $student->first_name, 'status' => 'approved']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.status', 'approved'));
    }

    public function test_subscriptions_page_handles_missing_dates_and_flags_expiring(): void
    {
        $plan = SubscriptionPlan::forceCreate(['name' => 'Pro', 'slug' => 'pro', 'interval' => 'month', 'price' => 50000]);
        $sub = fn (array $attrs) => Subscription::forceCreate(array_merge([
            'user_id' => $this->studentUser()->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ], $attrs));

        $soon = $sub(['started_at' => now()->subMonth(), 'ends_at' => now()->addDays(3), 'created_at' => now()->subMinutes(3)]);
        $far = $sub(['started_at' => now(), 'ends_at' => now()->addDays(30), 'created_at' => now()->subMinutes(2)]);
        $past = $sub(['started_at' => now()->subMonths(2), 'ends_at' => now()->subDays(3), 'created_at' => now()->subMinute()]);
        $trial = $sub(['status' => 'trial', 'started_at' => null, 'ends_at' => null, 'created_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Subscriptions/Index')
                ->has('subscriptions.data', 4)
                // latest() first
                ->where('subscriptions.data.0.id', $trial->id)
                ->where('subscriptions.data.0.ends_at', null)
                ->where('subscriptions.data.0.is_expiring_soon', false)
                ->where('subscriptions.data.1.id', $past->id)
                ->where('subscriptions.data.1.is_expiring_soon', false)
                ->where('subscriptions.data.2.id', $far->id)
                ->where('subscriptions.data.2.is_expiring_soon', false)
                ->where('subscriptions.data.3.id', $soon->id)
                ->where('subscriptions.data.3.is_expiring_soon', true));
    }

    /** QA-A-14: the details modal had no answer options */
    public function test_question_list_includes_the_options(): void
    {
        $course = $this->course($this->teacher());
        $question = \App\Models\Question::forceCreate(['course_id' => $course->id, 'type' => 'single_choice', 'question_text' => '2+2']);
        $question->options()->create(['option_text' => '4', 'is_correct' => true]);

        $this->actingAs($this->admin())->get(route('admin.questions.index'))
            ->assertInertia(fn ($page) => $page->where('questions.data.0.options.0.option_text', '4')
                ->where('questions.data.0.options.0.is_correct', true));
    }
}
