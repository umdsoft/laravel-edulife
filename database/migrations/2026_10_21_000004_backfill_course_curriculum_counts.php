<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * lessons_count / modules_count were only set by the bulk lesson form, so
 * courses showed e.g. "6 ta dars" after a lesson was deleted. Recount once;
 * Lesson/Module model events keep them in step from now on.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->orderBy('id')->each(function ($module) {
            $lessons = DB::table('lessons')->where('module_id', $module->id)->whereNull('deleted_at');
            DB::table('modules')->where('id', $module->id)->update([
                'lessons_count' => (clone $lessons)->count(),
                'total_duration' => (int) (clone $lessons)->sum('video_duration'),
            ]);
        });

        DB::table('courses')->orderBy('id')->each(function ($course) {
            DB::table('courses')->where('id', $course->id)->update([
                'modules_count' => DB::table('modules')->where('course_id', $course->id)->whereNull('deleted_at')->count(),
                'lessons_count' => DB::table('lessons')->where('course_id', $course->id)->whereNull('deleted_at')->count(),
            ]);
        });
    }

    public function down(): void
    {
        // counters only; nothing to undo
    }
};
