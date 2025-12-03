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
            $interests = implode(',', array_map(fn($id) => "'{$id}'", $profile->interests));
            $query->orderByRaw(
                "CASE WHEN direction_id IN ({$interests}) THEN 0 ELSE 1 END"
            );
        }
        
        // 2. O'xshash kurslar (yozilgan kurslar direction va tags)
        $enrolledCourses = $user->enrollments()->with('course.tags')->get();
        $directionIds = $enrolledCourses->pluck('course.direction_id')->unique();
        
        if ($directionIds->isNotEmpty()) {
            $directions = $directionIds->map(fn($id) => "'{$id}'")->implode(',');
            $query->orderByRaw(
                "CASE WHEN direction_id IN ({$directions}) THEN 0 ELSE 1 END"
            );
        }
        
        // 3. Yuqori reytingli kurslar
        $query->orderByDesc('reviews_avg_rating');
        
        // 4. Mashhur kurslar
        $query->orderByDesc('enrollments_count');
        
        return $query->limit($limit)->get();
    }
}
