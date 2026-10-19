<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links LMS content to the exam topic tree (Bosqich 2b, 4a.1) so lesson,
 * test, lab and exam results land on one topic map. All columns are
 * additive and nullable.
 *
 * courses.course_track: abituriyent | milliy_sertifikat | sat_math | school.
 */
return new class extends Migration
{
    // down() on SQLite rebuilds tables; see 2026_10_20_100002.
    public $withinTransaction = false;

    private const TABLES = ['courses', 'lessons', 'tests', 'lab_experiments'];

    public function up(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        foreach (self::TABLES as $name) {
            if (Schema::hasColumn($name, 'exam_topic_id')) {
                continue;
            }

            if ($sqlite) {
                // SQLite can add a nullable REFERENCES column in place. Laravel's
                // foreign() would rebuild the table and drop the CHECK
                // constraints of its enum columns.
                DB::statement(sprintf(
                    'ALTER TABLE "%s" ADD COLUMN "exam_topic_id" varchar REFERENCES "exam_topics"("id") ON DELETE SET NULL',
                    $name
                ));
                Schema::table($name, fn (Blueprint $table) => $table->index('exam_topic_id'));
            } else {
                Schema::table($name, function (Blueprint $table) {
                    $table->foreignUuid('exam_topic_id')->nullable()
                        ->constrained('exam_topics')->nullOnDelete();
                });
            }

            if ($name === 'courses') {
                Schema::table('courses', fn (Blueprint $table) => $table->string('course_track', 30)->nullable()->index());
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            if (! Schema::hasColumn($name, 'exam_topic_id')) {
                continue;
            }
            if ($name === 'courses') {
                Schema::table('courses', function (Blueprint $table) {
                    $table->dropIndex(['course_track']);
                    $table->dropColumn('course_track');
                });
            }
            Schema::table($name, function (Blueprint $table) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->dropIndex(['exam_topic_id']);
                }
                $table->dropConstrainedForeignId('exam_topic_id');
            });
        }
    }
};
