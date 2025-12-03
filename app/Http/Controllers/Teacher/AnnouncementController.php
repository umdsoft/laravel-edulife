<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Http\Requests\Teacher\StoreAnnouncementRequest;
use App\Jobs\SendAnnouncementNotificationsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('update', $course);
        
        $announcements = $course->announcements()
            ->with('teacher')
            ->latest()
            ->paginate(20);
        
        return Inertia::render('Teacher/Courses/Announcements/Index', [
            'course' => $course,
            'announcements' => $announcements,
        ]);
    }
    
    public function store(StoreAnnouncementRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $announcement = CourseAnnouncement::create([
            'course_id' => $course->id,
            'teacher_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type ?? 'info',
            'is_pinned' => $request->boolean('is_pinned'),
            'send_notification' => $request->boolean('send_notification', true),
            'published_at' => $request->publish_now ? now() : $request->published_at,
        ]);
        
        // Send notifications to enrolled students
        if ($announcement->send_notification && $announcement->published_at <= now()) {
            SendAnnouncementNotificationsJob::dispatch($announcement);
        }
        
        return back()->with('success', 'E\'lon yaratildi');
    }
    
    public function update(StoreAnnouncementRequest $request, Course $course, CourseAnnouncement $announcement)
    {
        $this->authorize('update', $course);
        
        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'is_pinned' => $request->boolean('is_pinned'),
        ]);
        
        return back()->with('success', 'E\'lon yangilandi');
    }
    
    public function destroy(Course $course, CourseAnnouncement $announcement)
    {
        $this->authorize('update', $course);
        
        $announcement->delete();
        
        return back()->with('success', 'E\'lon o\'chirildi');
    }
    
    public function togglePin(Course $course, CourseAnnouncement $announcement)
    {
        $this->authorize('update', $course);
        
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);
        
        return back()->with('success', $announcement->is_pinned ? 'E\'lon qadaldi' : 'E\'lon qadalmaydigan qilindi');
    }
}
