<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnglishModuleRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_english_routes_are_registered(): void
    {
        $english = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'english')
                || str_contains((string) $route->getName(), 'english'))
            ->map(fn ($route) => $route->uri());

        $this->assertEmpty($english->all(), 'English routes still registered: ' . $english->implode(', '));
    }

    public function test_english_url_returns_not_found(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($student)->get('/student/english')->assertNotFound();
    }

    public function test_fresh_schema_has_no_english_tables_and_general_user_achievements(): void
    {
        $this->assertFalse(Schema::hasTable('english_levels'));
        $this->assertFalse(Schema::hasTable('user_english_profiles'));
        $this->assertTrue(Schema::hasColumns('user_achievements', ['achievement_id', 'xp_rewarded', 'coin_rewarded', 'earned_at', 'is_claimed']));
    }

    public function test_student_dashboard_renders_without_english_props(): void
    {
        $this->withoutVite();
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Student/Dashboard')->missing('englishProgress'));
    }

    public function test_cleanup_migration_converts_a_legacy_database(): void
    {
        // Recreate the legacy (English module) shape of an existing database
        Schema::create('english_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::create('user_vocabulary', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::drop('user_achievements');
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('achievement_id');
            $table->integer('required_progress');
        });

        $migration = require database_path('migrations/2026_10_19_000001_remove_english_module_tables.php');
        $migration->up();

        $this->assertFalse(Schema::hasTable('english_levels'));
        $this->assertFalse(Schema::hasTable('user_vocabulary'));
        $this->assertFalse(Schema::hasColumn('user_achievements', 'required_progress'));
        $this->assertTrue(Schema::hasColumns('user_achievements', ['xp_rewarded', 'earned_at', 'is_claimed']));
    }
}
