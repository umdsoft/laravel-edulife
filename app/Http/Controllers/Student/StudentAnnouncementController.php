<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentAnnouncementController extends Controller
{
    use Concerns\LoadsCurriculumProgress;

    public function index(Course $course)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $user->enrollments()->where('course_id', $course->id)->firstOrFail();
        
        $announcements = $course->announcements()
            ->with('teacher')
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(20);
        
        return Inertia::render('Student/Learn/Announcements', [
            ...$this->curriculumProgress($course, $user),
            'course' => $course,
            'announcements' => $announcements,
        ]);
    }
}
