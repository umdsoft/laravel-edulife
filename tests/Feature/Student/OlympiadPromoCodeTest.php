<?php

namespace Tests\Feature\Student;

use App\Models\Olympiad;
use App\Models\OlympiadType;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Database\Seeders\Exam\ExamSeeder;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA2-A-10: a promo code made in the admin panel (QA2PROMO, 20%) was rejected by
 * the olympiad "Chegirma kodi" field, which only looked at olympiad coupons.
 */
class OlympiadPromoCodeTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function paidOlympiad(int $fee): Olympiad
    {
        $this->seed([OlympiadSeeder::class, ExamSeeder::class]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.olympiads.store'), [
            'title' => 'Pullik olimpiada',
            'olympiad_type_id' => OlympiadType::where('name', 'math')->value('id'),
            'registration_fee' => $fee,
            'olympiad_start_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'olympiad_end_at' => now()->addDay()->addHours(2)->format('Y-m-d\TH:i'),
            'create_default_sections' => true,
        ])->assertSessionHasNoErrors();
        $olympiad = Olympiad::firstOrFail();
        $section = $olympiad->sections()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.olympiads.sections.questions.assign', [$olympiad->id, $section->id]), ['count' => 1]);
        foreach (['upcoming', 'registration_open'] as $status) {
            $this->actingAs($admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => $status])->assertSessionHas('success');
        }

        // the admin promo code form
        $this->actingAs($admin)->post(route('admin.promo-codes.store'), [
            'code' => 'qa2promo', 'type' => 'percentage', 'value' => 20, 'max_uses_per_user' => 1, 'is_active' => true,
        ])->assertSessionHasNoErrors();

        return $olympiad->fresh();
    }

    public function test_an_admin_promo_code_discounts_the_olympiad_registration_once(): void
    {
        $olympiad = $this->paidOlympiad(20000);
        $student = $this->studentUser();

        $this->actingAs($student)->postJson(route('student.olympiads.coupon.validate', $olympiad->id), ['code' => 'QA2PROMO'])
            ->assertOk()
            ->assertJson(['valid' => true, 'discount_amount' => 4000, 'discount_type' => 'percent']);

        $this->actingAs($student)->postJson(route('student.olympiads.coupon.validate', $olympiad->id), ['code' => 'YOQ'])
            ->assertJson(['valid' => false]);

        $this->actingAs($student)->post(route('student.olympiads.register.store', $olympiad->slug), ['coupon_code' => 'qa2promo'])
            ->assertRedirect();

        $registration = $olympiad->registrations()->firstOrFail();
        $this->assertEquals(16000, (float) $registration->final_price);
        $this->assertEquals(4000, (float) $registration->discount_amount);

        $promo = PromoCode::firstOrFail();
        $this->assertSame(1, $promo->usage_count);
        $this->assertTrue(PromoCodeUsage::where('olympiad_registration_id', $registration->id)->exists());

        // per-user limit 1: the same student cannot use it again
        $this->actingAs($student)->postJson(route('student.olympiads.coupon.validate', $olympiad->id), ['code' => 'QA2PROMO'])
            ->assertJson(['valid' => false]);
    }

    public function test_a_wrong_code_is_an_uzbek_error_not_a_silent_full_price(): void
    {
        $olympiad = $this->paidOlympiad(20000);

        $this->actingAs($this->studentUser())->post(route('student.olympiads.register.store', $olympiad->slug), ['coupon_code' => 'NOTO'])
            ->assertSessionHas('error', "Promokod noto'g'ri yoki muddati o'tgan.");
        $this->assertSame(0, $olympiad->registrations()->count());
    }
}
