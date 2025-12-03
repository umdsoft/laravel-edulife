<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Http\Requests\Student\EnrollCourseRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $enrollments = Enrollment::with(['course.teacher', 'course.direction', 'lastLesson'])
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity_at')
            ->paginate(12);
        
        $stats = [
            'total' => Enrollment::where('user_id', $user->id)->count(),
            'in_progress' => Enrollment::where('user_id', $user->id)->where('is_completed', false)->count(),
            'completed' => Enrollment::where('user_id', $user->id)->where('is_completed', true)->count(),
        ];
        
        return Inertia::render('Student/MyCourses/Index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
        ]);
    }
    
    public function inProgress()
    {
        $user = Auth::user();
        
        $enrollments = Enrollment::with(['course.teacher', 'course.direction', 'lastLesson'])
            ->where('user_id', $user->id)
            ->where('is_completed', false)
            ->orderByDesc('last_activity_at')
            ->paginate(12);
        
        return Inertia::render('Student/MyCourses/InProgress', [
            'enrollments' => $enrollments,
        ]);
    }
    
    public function completed()
    {
        $user = Auth::user();
        
        $enrollments = Enrollment::with(['course.teacher', 'course.direction'])
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->orderByDesc('completed_at')
            ->paginate(12);
        
        return Inertia::render('Student/MyCourses/Completed', [
            'enrollments' => $enrollments,
        ]);
    }
    
    public function enroll(EnrollCourseRequest $request, Course $course)
    {
        $user = Auth::user();
        
        // Already enrolled check
        if ($user->isEnrolled($course)) {
            return back()->with('error', 'Siz allaqachon bu kursga yozilgansiz');
        }
        
        // Free course
        if ($course->is_free) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'total_lessons' => $course->lessons()->count(),
                'payment_status' => 'free',
                'access_type' => 'free',
                'status' => 'active',
            ]);
            
            // XP berish (kursga yozilish uchun)
            if ($user->studentProfile) {
                $user->studentProfile->addXp(10);
            }
            
            return redirect()->route('student.courses.show', $course)
                ->with('success', 'Kursga muvaffaqiyatli yozildingiz!');
        }
        
        // Paid course - payment page ga yo'naltirish
        // Note: Payment system is not fully implemented in this part, so we redirect to checkout or show error
        // Assuming route 'student.payment.checkout' exists or will exist.
        // For now, redirect to course show with error if payment not ready
        // return redirect()->route('student.payment.checkout', $course);
        return back()->with('error', 'Pullik kurslarga yozilish hozircha ishlamaydi.');
    }
}
