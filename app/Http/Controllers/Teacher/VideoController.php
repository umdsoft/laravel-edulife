<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Video;
use App\Services\VideoTranscodingService;
use App\Jobs\TranscodeVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function __construct(
        protected VideoTranscodingService $transcodingService
    ) {}
    
    /**
     * Upload video (standard upload for small files)
     */
    public function upload(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,mov,avi,webm', 'max:2097152'], // 2GB
        ]);
        
        // Delete old video if exists
        if ($lesson->video) {
            $this->deleteVideoFiles($lesson->video);
            $lesson->video()->delete();
        }
        
        // Store original video
        $file = $request->file('video');
        $videoId = Str::uuid();
        $originalPath = $file->storeAs(
            'videos/' . $lesson->id . '/original',
            $videoId . '.' . $file->getClientOriginalExtension(),
            'public'
        );
        
        // Create video record
        $video = Video::create([
            'lesson_id' => $lesson->id,
            'original_path' => $originalPath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'processing',
        ]);
        
        // Dispatch transcoding job
        TranscodeVideoJob::dispatch($video);
        
        return response()->json([
            'success' => true,
            'video' => $video,
            'message' => 'Video yuklandi. Transcoding jarayoni boshlandi.',
        ]);
    }
    
    /**
     * Chunked upload for large files
     */
    public function uploadChunk(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        $request->validate([
            'chunk' => ['required', 'file'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'upload_id' => ['required', 'string'],
            'filename' => ['required', 'string'],
        ]);
        
        $uploadId = $request->upload_id;
        $chunkIndex = $request->chunk_index;
        $totalChunks = $request->total_chunks;
        $filename = $request->filename;
        
        // Store chunk
        $chunkPath = 'videos/chunks/' . $uploadId;
        $request->file('chunk')->storeAs(
            $chunkPath,
            'chunk_' . str_pad($chunkIndex, 4, '0', STR_PAD_LEFT),
            'public'
        );
        
        // Check if all chunks uploaded
        $uploadedChunks = count(Storage::disk('public')->files($chunkPath));
        
        if ($uploadedChunks >= $totalChunks) {
            // Merge chunks
            $mergedPath = $this->mergeChunks($lesson, $uploadId, $filename, $totalChunks);
            
            // Delete old video if exists
            if ($lesson->video) {
                $this->deleteVideoFiles($lesson->video);
                $lesson->video()->delete();
            }
            
            // Create video record
            $video = Video::create([
                'lesson_id' => $lesson->id,
                'original_path' => $mergedPath,
                'original_name' => $filename,
                'size' => Storage::disk('public')->size($mergedPath),
                'mime_type' => Storage::disk('public')->mimeType($mergedPath),
                'status' => 'processing',
            ]);
            
            // Clean up chunks
            Storage::disk('public')->deleteDirectory($chunkPath);
            
            // Dispatch transcoding job
            TranscodeVideoJob::dispatch($video);
            
            return response()->json([
                'success' => true,
                'completed' => true,
                'video' => $video,
                'message' => 'Video yuklandi. Transcoding jarayoni boshlandi.',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'completed' => false,
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $totalChunks,
            'progress' => round(($uploadedChunks / $totalChunks) * 100),
        ]);
    }
    
    /**
     * Get video transcoding status
     */
    public function status(Lesson $lesson)
    {
        $video = $lesson->video;
        
        if (!$video) {
            return response()->json(['status' => 'no_video']);
        }
        
        return response()->json([
            'status' => $video->status,
            'progress' => $video->transcoding_progress,
            'qualities' => $video->qualities,
            'duration' => $video->duration,
            'error' => $video->error_message,
        ]);
    }
    
    /**
     * Delete video
     */
    public function destroy(Lesson $lesson)
    {
        $this->authorize('update', $lesson->course);
        
        if ($lesson->video) {
            $this->deleteVideoFiles($lesson->video);
            $lesson->video()->delete();
            $lesson->update(['duration' => 0]);
        }
        
        return back()->with('success', 'Video o\'chirildi');
    }
    
    /**
     * Merge uploaded chunks
     */
    private function mergeChunks(Lesson $lesson, string $uploadId, string $filename, int $totalChunks): string
    {
        $chunkPath = 'videos/chunks/' . $uploadId;
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mergedPath = 'videos/' . $lesson->id . '/original/' . Str::uuid() . '.' . $extension;
        
        // Create merged file
        $mergedFile = Storage::disk('public')->path($mergedPath);
        $mergedHandle = fopen($mergedFile, 'wb');
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = Storage::disk('public')->path($chunkPath . '/chunk_' . str_pad($i, 4, '0', STR_PAD_LEFT));
            $chunkHandle = fopen($chunkFile, 'rb');
            stream_copy_to_stream($chunkHandle, $mergedHandle);
            fclose($chunkHandle);
        }
        
        fclose($mergedHandle);
        
        return $mergedPath;
    }
    
    /**
     * Delete video files from storage
     */
    private function deleteVideoFiles(Video $video): void
    {
        // Delete original
        if ($video->original_path) {
            Storage::disk('public')->delete($video->original_path);
        }
        
        // Delete HLS directory
        $hlsPath = 'videos/' . $video->lesson_id . '/hls';
        if (Storage::disk('public')->exists($hlsPath)) {
            Storage::disk('public')->deleteDirectory($hlsPath);
        }
    }
}
