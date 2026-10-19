<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Http\Requests\Teacher\StoreAnnouncementRequest;
use App\Http\Requests\Teacher\UpdateAnnouncementRequest;
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
            // pinned first, then by publishing date (scheduled ones are in the future)
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
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
            'published_at' => $request->boolean('publish_now') ? now() : $request->published_at,
        ]);
        
        // Send notifications to enrolled students
        // a scheduled announcement notifies students when it goes live
        if ($announcement->send_notification) {
            SendAnnouncementNotificationsJob::dispatch($announcement)
                ->delay($announcement->published_at > now() ? $announcement->published_at : null);
        }
        
        return back()->with('success', 'E\'lon yaratildi');
    }
    
    public function update(UpdateAnnouncementRequest $request, CourseAnnouncement $announcement)
    {
        $course = $announcement->course;
        $this->authorize('update', $course);
        
        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'is_pinned' => $request->boolean('is_pinned'),
        ]);
        
        return back()->with('success', 'E\'lon yangilandi');
    }
    
    public function destroy(CourseAnnouncement $announcement)
    {
        $course = $announcement->course;
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
