<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\VideoWatchLog;
use App\Http\Requests\Student\UpdateProgressRequest;
use App\Services\ProgressTrackingService;
use App\Services\XPRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    public function __construct(
        protected ProgressTrackingService $progressService,
        protected XPRewardService $xpService
    ) {}
    
    /**
     * Update video progress (called periodically from player)
     */
    public function update(UpdateProgressRequest $request, Lesson $lesson)
    {
        $user = Auth::user();
        
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();
        
        // Update progress
        $progress->updateVideoProgress(
            $request->position,
            $request->duration
        );
        
        // Check if completed and award XP
        if ($progress->is_completed) {
            $this->xpService->awardLessonXP($progress);
        }
        
        // Update enrollment progress
        $this->progressService->updateEnrollmentProgress($progress->enrollment);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->fresh(),
        ]);
    }
    
    /**
     * Mark lesson as complete (manual or text lessons)
     */
    public function complete(Lesson $lesson)
    {
        $user = Auth::user();
        
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();
        
        if ($lesson->type === 'text') {
            $progress->markAsRead();
        } else {
            $progress->markAsCompleted();
        }
        
        // Award XP
        $this->xpService->awardLessonXP($progress);
        
        // Update enrollment progress
        $this->progressService->updateEnrollmentProgress($progress->enrollment);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->fresh(),
        ]);
    }
    
    /**
     * Log video watch session (for analytics)
     */
    public function logVideoWatch(Request $request, Lesson $lesson)
    {
        $request->validate([
            'start_position' => ['required', 'integer', 'min:0'],
            'end_position' => ['required', 'integer', 'min:0'],
            'duration' => ['required', 'integer', 'min:0'],
            'quality' => ['nullable', 'string'],
            'playback_rate' => ['nullable', 'numeric', 'min:0.25', 'max:2'],
        ]);
        
        $user = Auth::user();
        
        // Get progress and add watch time
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
        
        if ($progress) {
            $progress->addWatchTime($request->duration);
        }
        
        // Log watch session
        VideoWatchLog::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'video_id' => $lesson->video?->id,
            'start_position' => $request->start_position,
            'end_position' => $request->end_position,
            'duration' => $request->duration,
            'quality' => $request->quality,
            'playback_rate' => $request->playback_rate ?? 1.0,
            'device_type' => $this->detectDeviceType($request),
            'started_at' => now()->subSeconds($request->duration),
            'ended_at' => now(),
        ]);
        
        // Update student total watch time
        $user->studentProfile->increment('total_watch_time', $request->duration);
        
        // Update enrollment watch time
        if ($progress) {
            $progress->enrollment->increment('total_watch_time', $request->duration);
        }
        
        return response()->json(['success' => true]);
    }
    
    private function detectDeviceType(Request $request): string
    {
        $userAgent = strtolower($request->userAgent());
        
        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }
        
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}
