<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The platform teaches only Mathematics and Physics (owner decision 2026-10-19).
 *
 * Fresh installs never create the coding tables and seed only the two
 * directions; this migration converts databases created before that:
 *
 *  1. Drops the coding-problem tables (programming olympiad).
 *  2. Merges the duplicate 'mathematics' direction (created by the old
 *     OlympiadSeeder) into 'math': every reference is repointed, then the
 *     duplicate is deleted.
 *  3. Makes sure the 'math' and 'physics' directions exist and are active.
 *  4. DEACTIVATES every other direction (is_active = false). They are not
 *     deleted on purpose: courses, battles, tournaments and olympiad types
 *     cascade-delete with their direction, so deleting would destroy courses,
 *     enrollments and payment history. An admin can review and delete them.
 *  5. Deactivates olympiad types other than math/physics and adds the physics
 *     olympiad type when it is missing.
 *
 * Every step is guarded so it is safe on partial or already-converted schemas.
 */
return new class extends Migration
{
    private const CODING_TABLES = [
        'coding_submissions',
        'olympiad_coding_problems',
        'coding_problems',
    ];

    /** Tables with a direction_id foreign key. */
    private const DIRECTION_REFERENCES = [
        'courses',
        'olympiad_types',
        'battles',
        'tournaments',
        'question_bank',
        'certificate_templates',
    ];

    private const KEEP = ['math', 'physics'];

    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::CODING_TABLES as $table) {
                Schema::dropIfExists($table);
            }
        });

        // Fresh install: nothing to convert, DirectionSeeder / OlympiadSeeder create the data.
        if (! Schema::hasTable('directions') || ! DB::table('directions')->exists()) {
            return;
        }

        $mathId = $this->mergeMathDirections();
        $physicsId = $this->ensurePhysicsDirection();

        DB::table('directions')->whereIn('slug', self::KEEP)->update(['is_active' => true, 'updated_at' => now()]);
        DB::table('directions')->whereNotIn('slug', self::KEEP)->update(['is_active' => false, 'updated_at' => now()]);

        $this->recountCourses([$mathId, $physicsId]);
        $this->convertOlympiadTypes($physicsId);
    }

    public function down(): void
    {
        // Irreversible: the coding tables and their data were dropped, and the
        // duplicate 'mathematics' direction was merged into 'math'. Deactivated
        // directions can be re-enabled from the admin panel if ever needed.
    }

    /**
     * Returns the id of the single 'math' direction, merging 'mathematics' into it.
     */
    private function mergeMathDirections(): string
    {
        $math = DB::table('directions')->where('slug', 'math')->first();
        $duplicate = DB::table('directions')->where('slug', 'mathematics')->first();

        if (! $math && $duplicate) {
            // Only the duplicate exists: it becomes the canonical 'math' direction.
            DB::table('directions')->where('id', $duplicate->id)->update(['slug' => 'math', 'updated_at' => now()]);

            return $duplicate->id;
        }

        if (! $math) {
            return $this->insertDirection([
                'name_uz' => 'Matematika',
                'name_ru' => 'Математика',
                'name_en' => 'Mathematics',
                'slug' => 'math',
                'description' => 'Algebra, geometriya, trigonometriya va matematik analiz',
                'icon' => '📐',
                'color' => '#10B981',
                'sort_order' => 1,
            ]);
        }

        if ($duplicate) {
            foreach (self::DIRECTION_REFERENCES as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'direction_id')) {
                    DB::table($table)->where('direction_id', $duplicate->id)->update(['direction_id' => $math->id]);
                }
            }

            $this->repointStudentInterests($duplicate->id, $math->id);

            DB::table('directions')->where('id', $duplicate->id)->delete();
        }

        return $math->id;
    }

    /**
     * student_profiles.interests is a JSON array of direction ids (no FK).
     */
    private function repointStudentInterests(string $fromId, string $toId): void
    {
        if (! Schema::hasTable('student_profiles') || ! Schema::hasColumn('student_profiles', 'interests')) {
            return;
        }

        DB::table('student_profiles')
            ->where('interests', 'like', '%' . $fromId . '%')
            ->orderBy('id')
            ->get(['id', 'interests'])
            ->each(function ($profile) use ($fromId, $toId) {
                $interests = json_decode((string) $profile->interests, true);
                if (! is_array($interests)) {
                    return;
                }

                $interests = array_values(array_unique(array_map(
                    fn ($id) => $id === $fromId ? $toId : $id,
                    $interests
                )));

                DB::table('student_profiles')->where('id', $profile->id)->update(['interests' => json_encode($interests)]);
            });
    }

    private function ensurePhysicsDirection(): string
    {
        $physics = DB::table('directions')->where('slug', 'physics')->first();
        if ($physics) {
            return $physics->id;
        }

        // A physics direction an admin created under an Uzbek slug
        $existing = DB::table('directions')->where('slug', 'fizika')->first();
        if ($existing) {
            DB::table('directions')->where('id', $existing->id)->update(['slug' => 'physics', 'updated_at' => now()]);

            return $existing->id;
        }

        return $this->insertDirection([
            'name_uz' => 'Fizika',
            'name_ru' => 'Физика',
            'name_en' => 'Physics',
            'slug' => 'physics',
            'description' => "Mexanika, termodinamika, elektr va magnetizm, optika, to'lqinlar va atom fizikasi",
            'icon' => '⚛️',
            'color' => '#3B82F6',
            'sort_order' => 2,
        ]);
    }

    private function insertDirection(array $data): string
    {
        $id = (string) Str::uuid();

        $row = array_merge($data, [
            'id' => $id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasColumn('directions', 'courses_count')) {
            $row['courses_count'] = 0;
        }

        DB::table('directions')->insert($row);

        return $id;
    }

    private function recountCourses(array $directionIds): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('directions', 'courses_count')) {
            return;
        }

        foreach ($directionIds as $id) {
            DB::table('directions')->where('id', $id)->update([
                'courses_count' => DB::table('courses')->where('direction_id', $id)->count(),
            ]);
        }
    }

    private function convertOlympiadTypes(string $physicsDirectionId): void
    {
        // No olympiad types yet: OlympiadSeeder creates math and physics.
        if (! Schema::hasTable('olympiad_types') || ! DB::table('olympiad_types')->exists()) {
            return;
        }

        DB::table('olympiad_types')->whereNotIn('name', self::KEEP)->update(['is_active' => false, 'updated_at' => now()]);

        if (DB::table('olympiad_types')->where('name', 'physics')->exists()) {
            return;
        }

        DB::table('olympiad_types')->insert([
            'id' => (string) Str::uuid(),
            'direction_id' => $physicsDirectionId,
            'name' => 'physics',
            'display_name' => 'Physics Olympiad',
            'display_name_uz' => 'Fizika olimpiadasi',
            'description' => 'Physics olympiad covering mechanics, thermodynamics, electricity and magnetism, optics and waves, and atomic physics.',
            'sections' => json_encode(['test']),
            'section_weights' => json_encode(['test' => 100]),
            'icon' => '⚛️',
            'color' => '#3B82F6',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
