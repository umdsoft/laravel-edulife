<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\VideoChapter;
use App\Http\Requests\Teacher\StoreVideoChapterRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VideoChapterController extends Controller
{
    public function index(Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $chapters = $lesson->video->chapters()
            ->orderBy('timestamp')
            ->get();
            
        return Inertia::render('Teacher/Courses/Curriculum/VideoChapters', [
            'lesson' => $lesson->load('video'),
            'chapters' => $chapters,
        ]);
    }
    
    public function store(StoreVideoChapterRequest $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        if (!$lesson->video) {
            return back()->withErrors(['video' => 'Darsda video mavjud emas']);
        }
        
        $chapter = VideoChapter::create([
            'video_id' => $lesson->video->id,
            'lesson_id' => $lesson->id,
            'title' => $request->title,
            'timestamp' => $request->timestamp,
            'description' => $request->description,
            'sort_order' => $request->timestamp, // Sort by timestamp by default
        ]);
        
        return back()->with('success', 'Video bo\'limi yaratildi');
    }
    
    public function update(StoreVideoChapterRequest $request, Lesson $lesson, VideoChapter $chapter)
    {
        $this->authorize('update', $lesson->course);
        
        $chapter->update([
            'title' => $request->title,
            'timestamp' => $request->timestamp,
            'description' => $request->description,
            'sort_order' => $request->timestamp,
        ]);
        
        return back()->with('success', 'Video bo\'limi yangilandi');
    }
    
    public function destroy(Lesson $lesson, VideoChapter $chapter)
    {
        $this->authorize('update', $lesson->course);
        
        $chapter->delete();
        
        return back()->with('success', 'Video bo\'limi o\'chirildi');
    }
}
