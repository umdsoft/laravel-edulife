<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Collection;

class CourseRecommendationService
{
    /**
     * Get personalized recommendations for user
     */
    public function getRecommendations(User $user, int $limit = 8): Collection
    {
        $enrolledCourseIds = $user->enrollments()->pluck('course_id');
        $profile = $user->studentProfile;
        
        $query = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
            ->whereNotIn('id', $enrolledCourseIds)
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating');
        
        // 1. Qiziqishlar asosida
        if ($profile && $profile->interests && count($profile->interests) > 0) {
            $interests = array_values($profile->interests);
            $placeholders = implode(',', array_fill(0, count($interests), '?'));
            $query->orderByRaw(
                "CASE WHEN direction_id IN ({$placeholders}) THEN 0 ELSE 1 END",
                $interests
            );
        }
        
        // 2. O'xshash kurslar (yozilgan kurslar direction va tags)
        // Only the direction ids are needed: one query instead of loading every enrollment with tags
        $directionIds = Course::whereIn('id', $enrolledCourseIds)
            ->distinct()
            ->pluck('direction_id')
            ->filter()
            ->values()
            ->all();

        if (count($directionIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($directionIds), '?'));
            $query->orderByRaw(
                "CASE WHEN direction_id IN ({$placeholders}) THEN 0 ELSE 1 END",
                $directionIds
            );
        }
        
        // 3. Yuqori reytingli kurslar
        $query->orderByDesc('reviews_avg_rating');
        
        // 4. Mashhur kurslar
        $query->orderByDesc('enrollments_count');
        
        return $query->limit($limit)->get();
    }
}
