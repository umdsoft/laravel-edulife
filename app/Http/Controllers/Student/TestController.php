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
            // lesson/module tests point to their lesson/module through testable
            ->with('testable')
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
                $test->can_retake = $test->allowsAnotherAttempt($attempts->count());
                $test->has_active_attempt = $attempts->where('status', 'in_progress')->isNotEmpty();
                $test->pass_rate = (int) round((float) $test->passing_score);
                
                return $test;
            });
        
        // Group by type
        // tests.type is lesson / module / final
        $groupedTests = [
            'lesson_tests' => $tests->whereIn('type', ['lesson', 'lesson_test'])->values(),
            'module_tests' => $tests->whereIn('type', ['module', 'module_test'])->values(),
            'final_test' => $tests->whereIn('type', ['final', 'final_test'])->first(),
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
        
        // max_attempts = 0 means unlimited
        $canStart = $test->allowsAnotherAttempt($previousAttempts->count());
        $bestScore = $previousAttempts->max('score') ?? 0;
        $isPassed = $previousAttempts->where('is_passed', true)->isNotEmpty();
        
        // lesson test → lesson done, module test → module done, final → 80% of the course
        $prerequisiteError = $test->prerequisiteError($enrollment);
        $prerequisitesMet = $prerequisiteError === null;
        
        $test->loadCount('questions');
        
        return Inertia::render('Student/Tests/Start', [
            'test' => $test,
            'course' => $test->course,
            'previousAttempts' => $previousAttempts,
            'canStart' => $canStart && $prerequisitesMet,
            'prerequisitesMet' => $prerequisitesMet,
            'prerequisiteMessage' => $prerequisiteError,
            'questionTypes' => $test->questions()->distinct()->pluck('type')->values(),
            'bestScore' => $bestScore,
            'isPassed' => $isPassed,
            'remainingAttempts' => $test->remainingAttempts($previousAttempts->count()), // null = unlimited
        ]);
    }
}
