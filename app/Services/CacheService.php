<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Direction;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing application caching.
 * 
 * Provides centralized cache management for frequently accessed data
 * to improve application performance.
 * 
 * @package App\Services
 */
class CacheService
{
    /**
     * Cache TTL values in seconds.
     */
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 3600;    // 1 hour
    const TTL_LONG = 86400;     // 24 hours
    const TTL_WEEK = 604800;    // 1 week

    /**
     * Get all active directions (cached).
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveDirections()
    {
        return Cache::remember('directions.active', self::TTL_LONG, function () {
            return Direction::where('is_active', true)
                ->withCount('courses')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Get featured courses (cached).
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedCourses(int $limit = 8)
    {
        return Cache::remember("courses.featured.{$limit}", self::TTL_MEDIUM, function () use ($limit) {
            return Course::where('status', 'published')
                ->where('is_featured', true)
                ->with(['teacher:id,first_name,last_name,avatar', 'direction:id,name,slug'])
                ->withCount('enrollments')
                ->withAvg('reviews', 'rating')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get popular courses (cached).
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularCourses(int $limit = 8)
    {
        return Cache::remember("courses.popular.{$limit}", self::TTL_MEDIUM, function () use ($limit) {
            return Course::where('status', 'published')
                ->with(['teacher:id,first_name,last_name,avatar', 'direction:id,name,slug'])
                ->withCount('enrollments')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('enrollments_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get new courses (cached).
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNewCourses(int $limit = 8)
    {
        return Cache::remember("courses.new.{$limit}", self::TTL_SHORT, function () use ($limit) {
            return Course::where('status', 'published')
                ->with(['teacher:id,first_name,last_name,avatar', 'direction:id,name,slug'])
                ->withCount('enrollments')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get course by slug (cached).
     * 
     * @param string $slug
     * @return Course|null
     */
    public function getCourseBySlug(string $slug)
    {
        return Cache::remember("course.{$slug}", self::TTL_SHORT, function () use ($slug) {
            return Course::where('slug', $slug)
                ->where('status', 'published')
                ->with([
                    'teacher:id,first_name,last_name,avatar',
                    'direction:id,name,slug',
                    'modules.lessons',
                    'reviews.user:id,first_name,last_name,avatar',
                ])
                ->withCount(['enrollments', 'reviews', 'lessons'])
                ->withAvg('reviews', 'rating')
                ->first();
        });
    }

    /**
     * Get statistics for dashboard (cached).
     * 
     * @return array
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('stats.dashboard', self::TTL_SHORT, function () {
            return [
                'total_courses' => Course::where('status', 'published')->count(),
                'total_students' => \App\Models\User::where('role', 'student')->count(),
                'total_teachers' => \App\Models\User::where('role', 'teacher')->count(),
                'total_directions' => Direction::where('is_active', true)->count(),
            ];
        });
    }

    /**
     * Clear course-related caches.
     * 
     * @param string|null $slug
     */
    public function clearCourseCaches(?string $slug = null): void
    {
        // Clear specific course cache
        if ($slug) {
            Cache::forget("course.{$slug}");
        }

        // Clear list caches
        Cache::forget('courses.featured.8');
        Cache::forget('courses.popular.8');
        Cache::forget('courses.new.8');
        Cache::forget('stats.dashboard');
    }

    /**
     * Clear direction caches.
     */
    public function clearDirectionCaches(): void
    {
        Cache::forget('directions.active');
    }

    /**
     * Clear all application caches.
     */
    public function clearAllCaches(): void
    {
        $this->clearCourseCaches();
        $this->clearDirectionCaches();
        Cache::forget('stats.dashboard');
    }
}
