<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Direction;
use App\Models\OlympiadType;
use App\Models\User;
use Database\Seeders\DirectionSeeder;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The platform teaches only Mathematics and Physics.
 */
class MathPhysicsOnlyTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/2026_10_19_000002_keep_only_math_and_physics.php';

    // ==================== SEEDERS ====================

    public function test_direction_seeder_seeds_only_math_and_physics(): void
    {
        $this->seed(DirectionSeeder::class);
        $this->seed(DirectionSeeder::class); // idempotent

        $this->assertSame(['math', 'physics'], Direction::orderBy('sort_order')->pluck('slug')->all());

        $physics = Direction::where('slug', 'physics')->firstOrFail();
        $this->assertSame('Fizika', $physics->name_uz);
        $this->assertSame('Физика', $physics->name_ru);
        $this->assertSame('Physics', $physics->name_en);
        $this->assertTrue($physics->is_active);
        $this->assertNotEmpty($physics->icon);
        $this->assertNotEmpty($physics->description);
    }

    public function test_olympiad_seeder_reuses_directions_and_seeds_only_math_and_physics_types(): void
    {
        $this->seed(DirectionSeeder::class);
        $this->seed(OlympiadSeeder::class);

        // No duplicate 'mathematics' / 'russian-language' / 'programming' directions
        $this->assertSame(['math', 'physics'], Direction::orderBy('slug')->pluck('slug')->all());

        $types = OlympiadType::with('direction')->orderBy('name')->get();
        $this->assertSame(['math', 'physics'], $types->pluck('name')->all());
        $this->assertSame(['math', 'physics'], $types->pluck('direction.slug')->all());

        foreach ($types as $type) {
            $this->assertSame(['test'], $type->sections);
        }
    }

    public function test_olympiad_seeder_alone_creates_the_math_and_physics_directions(): void
    {
        $this->seed(OlympiadSeeder::class);

        $this->assertSame(['math', 'physics'], Direction::orderBy('slug')->pluck('slug')->all());
    }

    public function test_programming_and_russian_olympiad_types_no_longer_exist(): void
    {
        $this->assertFalse(defined(OlympiadType::class . '::TYPE_PROGRAMMING'));
        $this->assertFalse(defined(OlympiadType::class . '::TYPE_LANGUAGE_RUSSIAN'));
        $this->assertFalse(defined(OlympiadType::class . '::SECTION_CODING'));
        $this->assertSame(['math', 'physics'], OlympiadType::TYPES);

        $physics = new OlympiadType(['name' => OlympiadType::TYPE_PHYSICS]);
        $config = $physics->getDefaultSectionsConfig();
        $this->assertSame(['test'], array_keys($config));
        $this->assertSame(30, array_sum($config['test']['categories']));
        $this->assertSame($config['test']['questions_count'], array_sum($config['test']['categories']));
        $this->assertSame(120, $physics->getTotalDurationMinutes());
        $this->assertTrue($physics->is_physics_type);

        $this->assertSame([], (new OlympiadType(['name' => 'programming']))->getDefaultSectionsConfig());
    }

    public function test_fresh_schema_has_no_coding_tables(): void
    {
        foreach (['coding_problems', 'olympiad_coding_problems', 'coding_submissions'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "{$table} should not exist");
        }
        foreach (['CodingProblem', 'CodingSubmission', 'OlympiadCodingProblem'] as $model) {
            $this->assertFileDoesNotExist(app_path("Models/{$model}.php"));
        }
    }

    // ==================== STUDENT LISTINGS ====================

    public function test_students_see_only_active_directions_and_their_courses(): void
    {
        $this->withoutVite();
        $this->seed(DirectionSeeder::class);
        $legacy = Direction::factory()->create(['slug' => 'programming', 'name_uz' => 'Dasturlash', 'is_active' => false]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $mathCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'direction_id' => Direction::where('slug', 'math')->value('id'),
            'status' => 'published',
            'title' => 'Algebra asoslari',
        ]);
        $legacyCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'direction_id' => $legacy->id,
            'status' => 'published',
            'title' => 'Algebra uchun Python',
        ]);

        $slugs = fn ($directions) => collect($directions)->pluck('slug')->sort()->values()->all();

        $this->actingAs($student)->get('/student/courses')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Courses/Index')
                ->where('directions', fn ($d) => $slugs($d) === ['math', 'physics'])
                ->where('courses.data', fn ($c) => collect($c)->pluck('id')->all() === [$mathCourse->id]));

        $this->actingAs($student)->get('/student/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('directions', fn ($d) => $slugs($d) === ['math', 'physics'])
                ->where('popularCourses', fn ($c) => ! collect($c)->pluck('id')->contains($legacyCourse->id))
                ->where('newCourses', fn ($c) => ! collect($c)->pluck('id')->contains($legacyCourse->id)));

        $this->actingAs($student)->get('/student/search')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('directions', fn ($d) => $slugs($d) === ['math', 'physics']));

        $this->actingAs($student)->get('/student/search?q=Algebra')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('directions', fn ($d) => $slugs($d) === ['math', 'physics'])
                ->where('courses.data', fn ($c) => collect($c)->pluck('id')->all() === [$mathCourse->id]));

        $this->actingAs($student)->getJson('/student/search/suggestions?q=Algebra')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $mathCourse->id);

        $this->actingAs($student)->get('/student/battles')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('directions', fn ($d) => $slugs($d) === ['math', 'physics']));

        $this->actingAs($student)->get('/student/courses/category/' . $legacy->id)->assertNotFound();
        $this->actingAs($student)
            ->get('/student/courses/category/' . Direction::where('slug', 'physics')->value('id'))
            ->assertOk();
    }

    // ==================== CLEANUP MIGRATION ====================

    public function test_cleanup_migration_converts_a_legacy_database(): void
    {
        // Legacy shape: coding tables, old DirectionSeeder + OlympiadSeeder rows
        $this->createLegacyCodingTables();
        $ids = [];
        foreach (['programming', 'russian', 'math', 'marketing', 'design', 'business', 'russian-language', 'mathematics'] as $slug) {
            $ids[$slug] = $this->insertDirection($slug);
        }

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $onDuplicate = Course::factory()->create(['teacher_id' => $teacher->id, 'direction_id' => $ids['mathematics']]);
        $onProgramming = Course::factory()->create(['teacher_id' => $teacher->id, 'direction_id' => $ids['programming']]);
        $onMath = Course::factory()->create(['teacher_id' => $teacher->id, 'direction_id' => $ids['math']]);

        foreach (['language_russian' => 'russian-language', 'programming' => 'programming', 'math' => 'mathematics'] as $name => $slug) {
            $this->insertOlympiadType($name, $ids[$slug]);
        }
        $questionId = (string) Str::uuid();
        DB::table('question_bank')->insert([
            'id' => $questionId, 'section_type' => 'test', 'direction_id' => $ids['mathematics'],
            'question_type' => 'single_choice', 'question_text' => '2+2?', 'correct_answer' => '"4"',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // User::created already made the student's profile
        DB::table('student_profiles')->where('user_id', $student->id)->update([
            'interests' => json_encode([$ids['mathematics'], $ids['math'], $ids['programming']]),
        ]);

        $this->runCleanupMigration();

        // Coding tables dropped
        $this->assertFalse(Schema::hasTable('coding_problems'));
        $this->assertFalse(Schema::hasTable('olympiad_coding_problems'));
        $this->assertFalse(Schema::hasTable('coding_submissions'));

        // Duplicate merged into 'math'
        $this->assertNull(Direction::where('slug', 'mathematics')->first());
        $this->assertSame($ids['math'], $onDuplicate->fresh()->direction_id);
        $this->assertSame($ids['math'], $onMath->fresh()->direction_id);
        $this->assertSame($ids['math'], OlympiadType::where('name', 'math')->value('direction_id'));
        $this->assertSame($ids['math'], DB::table('question_bank')->where('id', $questionId)->value('direction_id'));
        $this->assertSame(2, Direction::find($ids['math'])->courses_count);
        $this->assertSame(
            [$ids['math'], $ids['programming']],
            json_decode(DB::table('student_profiles')->where('user_id', $student->id)->value('interests'), true)
        );

        // Physics created; only math/physics active; others deactivated, NOT deleted
        $this->assertSame(['math', 'physics'], Direction::active()->orderBy('slug')->pluck('slug')->all());
        $this->assertSame(
            ['business', 'design', 'marketing', 'programming', 'russian', 'russian-language'],
            Direction::where('is_active', false)->orderBy('slug')->pluck('slug')->all()
        );
        $this->assertNotNull($onProgramming->fresh(), 'courses of deactivated directions must survive');

        // Olympiad types
        $this->assertSame(['math', 'physics'], OlympiadType::active()->orderBy('name')->pluck('name')->all());
        $this->assertSame(['language_russian', 'programming'], OlympiadType::where('is_active', false)->orderBy('name')->pluck('name')->all());
        $this->assertSame('physics', OlympiadType::where('name', 'physics')->first()->direction->slug);

        // Running it again changes nothing
        $this->runCleanupMigration();
        $this->assertSame(8, Direction::count()); // 8 legacy - 1 merged duplicate + physics
        $this->assertSame(4, OlympiadType::count());
    }

    public function test_cleanup_migration_promotes_the_duplicate_when_math_is_missing(): void
    {
        $mathematics = $this->insertDirection('mathematics');
        $programming = $this->insertDirection('programming');
        $fizika = $this->insertDirection('fizika');

        $this->runCleanupMigration();

        $this->assertSame($mathematics, Direction::where('slug', 'math')->value('id'));
        $this->assertSame($fizika, Direction::where('slug', 'physics')->value('id'));
        $this->assertSame(3, Direction::count());
        $this->assertFalse(Direction::find($programming)->is_active);
    }

    public function test_cleanup_migration_is_a_no_op_on_a_fresh_install(): void
    {
        $this->runCleanupMigration();

        $this->assertSame(0, Direction::count());
        $this->assertSame(0, OlympiadType::count());
    }

    // ==================== HELPERS ====================

    private function runCleanupMigration(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->up();
    }

    private function insertDirection(string $slug): string
    {
        $id = (string) Str::uuid();
        DB::table('directions')->insert([
            'id' => $id, 'name_uz' => ucfirst($slug), 'slug' => $slug, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function insertOlympiadType(string $name, string $directionId): void
    {
        DB::table('olympiad_types')->insert([
            'id' => (string) Str::uuid(), 'direction_id' => $directionId, 'name' => $name,
            'display_name' => $name, 'display_name_uz' => $name,
            'sections' => '["test"]', 'section_weights' => '{"test":100}', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createLegacyCodingTables(): void
    {
        Schema::create('coding_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::create('olympiad_coding_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('problem_id')->constrained('coding_problems')->cascadeOnDelete();
        });
        Schema::create('coding_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_problem_id')->constrained('olympiad_coding_problems')->cascadeOnDelete();
            $table->foreignUuid('problem_id')->constrained('coding_problems')->cascadeOnDelete();
        });
    }
}
