<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_use_demo_password_outside_local(): void
    {
        $this->assertFalse(app()->isLocal());

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->assertFalse(Hash::check('admin123', $admin->password));
    }

    public function test_demo_test_users_are_not_seeded_in_production(): void
    {
        $this->app['env'] = 'production';

        // Run directly: db:seed would prompt for confirmation in production
        app(TestUserSeeder::class)->run();

        $this->assertSame(0, User::where('email', 'superadmin@test.uz')->count());
    }
}
