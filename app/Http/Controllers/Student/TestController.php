<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TestController extends Controller
{
    /**
     * List all tests for a course
     */
    public function index(Course $course)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->firstOrFail();
        
        // Get tests with user's attempts
        $tests = Test::where('course_id', $course->id)
            ->where('is_active', true)
            ->with(['module', 'lesson'])
            ->withCount('questions')
            ->get()
            ->map(function ($test) use ($user) {
                // Get user's attempts for this test
                $attempts = TestAttempt::where('user_id', $user->id)
                    ->where('test_id', $test->id)
                    ->orderByDesc('created_at')
                    ->get();
                
                $test->user_attempts = $attempts;
                $test->attempts_count = $attempts->count();
                $test->best_score = $attempts->max('score') ?? 0;
                $test->is_passed = $attempts->where('is_passed', true)->isNotEmpty();
                $test->can_retake = $attempts->count() < $test->max_attempts;
                $test->has_active_attempt = $attempts->where('status', 'in_progress')->isNotEmpty();
                
                return $test;
            });
        
        // Group by type
        $groupedTests = [
            'lesson_tests' => $tests->where('type', 'lesson_test')->values(),
            'module_tests' => $tests->where('type', 'module_test')->values(),
            'final_test' => $tests->where('type', 'final_test')->first(),
        ];
        
        return Inertia::render('Student/Tests/Index', [
            'course' => $course,
            'tests' => $groupedTests,
            'enrollment' => $enrollment,
        ]);
    }
    
    /**
     * Test history for all courses
     */
    public function history()
    {
        $user = Auth::user();
        
        $attempts = TestAttempt::with(['test', 'course'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->orderByDesc('submitted_at')
            ->paginate(20);
        
        // Stats
        $stats = [
            'total_attempts' => TestAttempt::where('user_id', $user->id)->count(),
            'passed' => TestAttempt::where('user_id', $user->id)->where('is_passed', true)->count(),
            'failed' => TestAttempt::where('user_id', $user->id)->where('is_passed', false)->whereIn('status', ['submitted', 'expired'])->count(),
            'average_score' => TestAttempt::where('user_id', $user->id)->whereIn('status', ['submitted', 'expired'])->avg('score') ?? 0,
        ];
        
        return Inertia::render('Student/Tests/History', [
            'attempts' => $attempts,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Pre-test info page
     */
    public function start(Test $test)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $enrollment = $user->enrollments()
            ->where('course_id', $test->course_id)
            ->firstOrFail();
        
        // Check for active attempt
        $activeAttempt = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->where('status', 'in_progress')
            ->first();
        
        if ($activeAttempt) {
            // Resume active attempt
            return redirect()->route('student.tests.attempt', $activeAttempt);
        }
        
        // Count previous attempts
        $previousAttempts = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->orderByDesc('created_at')
            ->get();
        
        $canStart = $previousAttempts->count() < $test->max_attempts;
        $bestScore = $previousAttempts->max('score') ?? 0;
        $isPassed = $previousAttempts->where('is_passed', true)->isNotEmpty();
        
        // Check prerequisites (e.g., complete lessons first)
        $prerequisitesMet = $this->checkPrerequisites($test, $enrollment);
        
        $test->loadCount('questions');
        
        return Inertia::render('Student/Tests/Start', [
            'test' => $test,
            'course' => $test->course,
            'previousAttempts' => $previousAttempts,
            'canStart' => $canStart && $prerequisitesMet,
            'prerequisitesMet' => $prerequisitesMet,
            'bestScore' => $bestScore,
            'isPassed' => $isPassed,
            'remainingAttempts' => max(0, $test->max_attempts - $previousAttempts->count()),
        ]);
    }
    
    /**
     * Check if prerequisites are met
     */
    private function checkPrerequisites(Test $test, $enrollment): bool
    {
        // For lesson_test: lesson must be completed
        if ($test->type === 'lesson_test' && $test->lesson_id) {
            $lessonProgress = $enrollment->lessonProgresses()
                ->where('lesson_id', $test->lesson_id)
                ->first();
            
            return $lessonProgress && $lessonProgress->is_completed;
        }
        
        // For module_test: all lessons in module must be completed
        if ($test->type === 'module_test' && $test->module_id) {
            $moduleLessons = $test->module->lessons()->pluck('id');
            $completedLessons = $enrollment->lessonProgresses()
                ->whereIn('lesson_id', $moduleLessons)
                ->where('is_completed', true)
                ->count();
            
            return $completedLessons >= $moduleLessons->count();
        }
        
        // For final_test: course progress >= 80%
        if ($test->type === 'final_test') {
            return $enrollment->progress >= 80;
        }
        
        return true;
    }
}
