<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The teacher profile form saves "expertise" (skills list) and
 * "experience_years", but teacher_profiles never had those columns, and no
 * code ever created a teacher_profiles row, so saving the profile crashed.
 *
 * Adds the two columns and creates the missing row for every existing teacher
 * (new teachers get one from the User model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_profiles', 'expertise')) {
                $table->json('expertise')->nullable()->after('specializations');
            }
            if (! Schema::hasColumn('teacher_profiles', 'experience_years')) {
                $table->unsignedSmallInteger('experience_years')->nullable()->after('expertise');
            }
        });

        $teachers = DB::table('users')
            ->where('role', 'teacher')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('teacher_profiles')
                ->whereColumn('teacher_profiles.user_id', 'users.id'))
            ->pluck('id');

        foreach ($teachers as $userId) {
            DB::table('teacher_profiles')->insert([
                'id' => (string) Str::uuid7(),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            foreach (['expertise', 'experience_years'] as $column) {
                if (Schema::hasColumn('teacher_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
