<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admin moderation changed a review's status without refreshing the course's
 * avg_rating / reviews_count, so cards showed "4.0 (0)". Recount both from the
 * approved reviews (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $stats = DB::table('reviews')
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->groupBy('course_id')
            ->selectRaw('course_id, AVG(rating) AS avg_rating, COUNT(*) AS reviews_count')
            ->get()
            ->keyBy('course_id');

        DB::table('courses')->orderBy('id')->select('id')->chunk(200, function ($courses) use ($stats) {
            foreach ($courses as $course) {
                $row = $stats->get($course->id);
                DB::table('courses')->where('id', $course->id)->update([
                    'avg_rating' => round((float) ($row->avg_rating ?? 0), 2),
                    'reviews_count' => (int) ($row->reviews_count ?? 0),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Data correction only.
    }
};
