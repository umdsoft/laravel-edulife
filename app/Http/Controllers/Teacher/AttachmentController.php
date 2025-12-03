<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Http\Requests\Teacher\StoreAttachmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttachmentController extends Controller
{
    public function index(Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $attachments = $lesson->attachments()
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Teacher/Courses/Attachments/Index', [
            'lesson' => $lesson->load(['module', 'course']),
            'attachments' => $attachments,
        ]);
    }
    
    public function store(StoreAttachmentRequest $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $file = $request->file('file');
        $maxOrder = $lesson->attachments()->max('sort_order') ?? 0;
        
        // Store file
        $path = $file->store('lessons/' . $lesson->id . '/attachments', 'public');
        
        // Get file type from extension
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = $this->getFileType($extension);
        
        $attachment = LessonAttachment::create([
            'lesson_id' => $lesson->id,
            'title' => $request->title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $fileType,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_downloadable' => $request->boolean('is_downloadable', true),
            'sort_order' => $maxOrder + 1,
        ]);
        
        return back()->with('success', 'Fayl yuklandi');
    }
    
    public function update(Request $request, Lesson $lesson, LessonAttachment $attachment)
    {
        $this->authorize('update', $lesson->course);
        
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_downloadable' => ['boolean'],
        ]);
        
        $attachment->update([
            'title' => $request->title,
            'is_downloadable' => $request->boolean('is_downloadable'),
        ]);
        
        return back()->with('success', 'Fayl yangilandi');
    }
    
    public function destroy(Lesson $lesson, LessonAttachment $attachment)
    {
        $this->authorize('update', $lesson->course);
        
        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);
        
        $attachment->delete();
        
        return back()->with('success', 'Fayl o\'chirildi');
    }
    
    public function reorder(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*.id' => ['required', 'uuid'],
            'attachments.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        
        foreach ($request->attachments as $item) {
            LessonAttachment::where('id', $item['id'])
                ->where('lesson_id', $lesson->id)
                ->update(['sort_order' => $item['sort_order']]);
        }
        
        return back()->with('success', 'Tartib saqlandi');
    }
    
    private function getFileType(string $extension): string
    {
        $types = [
            'pdf' => 'pdf',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'spreadsheet',
            'xlsx' => 'spreadsheet',
            'ppt' => 'presentation',
            'pptx' => 'presentation',
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'txt' => 'text',
            'md' => 'text',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'mp3' => 'audio',
            'wav' => 'audio',
            'mp4' => 'video',
            'mov' => 'video',
            'js' => 'code',
            'py' => 'code',
            'html' => 'code',
            'css' => 'code',
            'json' => 'code',
        ];
        
        return $types[$extension] ?? 'other';
    }
}
