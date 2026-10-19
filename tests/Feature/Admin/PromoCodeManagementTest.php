<?php

namespace Tests\Feature\Admin;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PromoCodeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** Payload exactly as the Admin/PromoCodes/Index form posts it. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'yangi20',
            'type' => 'percentage',
            'value' => 20,
            'min_amount' => 100000,
            'max_uses' => 50,
            'max_uses_per_user' => 2,
            'is_active' => true,
        ], $overrides);
    }

    public function test_admin_can_create_a_promo_code_from_the_form_payload(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.promo-codes.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $code = PromoCode::where('code', 'YANGI20')->firstOrFail();
        $this->assertEquals(100000, $code->min_purchase);
        $this->assertSame(50, $code->usage_limit);
        $this->assertSame(2, $code->per_user_limit);
        $this->assertSame('all', $code->applies_to);
        $this->assertSame($admin->id, $code->created_by);
    }

    public function test_update_maps_fields_and_index_exposes_form_keys(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.promo-codes.store'), $this->payload());
        $code = PromoCode::firstOrFail();
        $code->update(['usage_count' => 50]);

        $this->actingAs($admin)
            ->put(route('admin.promo-codes.update', $code), $this->payload(['max_uses' => 100, 'min_amount' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame(100, $code->fresh()->usage_limit);
        $this->assertNull($code->fresh()->min_purchase);

        $this->actingAs($admin)
            ->get(route('admin.promo-codes.index', ['status' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PromoCodes/Index')
                ->has('promoCodes.data', 1)
                ->where('promoCodes.data.0.max_uses', 100)
                ->where('promoCodes.data.0.max_uses_per_user', 2)
                ->where('promoCodes.data.0.used_count', 50)
                ->where('promoCodes.data.0.is_maxed_out', false));
    }
}
