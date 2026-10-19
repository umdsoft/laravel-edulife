<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** QA-A-04: the dashboard showed hard-coded +12% / +5% / +8% / +15%. */
class AdminDashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_trends_are_computed_from_the_last_two_months(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        // 2 users registered 31-59 days ago, 3 (incl. the admin) in the last 30 days
        User::factory()->count(2)->create(['created_at' => now()->subDays(45)]);
        User::factory()->count(2)->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('stats.users.trend', 50)
                ->where('stats.revenue.trend', null)
                ->where('stats.courses.trend', null));
    }
}
