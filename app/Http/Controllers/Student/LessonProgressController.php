<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StudentProfile;
use App\Models\VideoWatchLog;
use App\Http\Requests\Student\UpdateProgressRequest;
use App\Services\ProgressTrackingService;
use App\Services\MissionService;
use App\Services\XPRewardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LessonProgressController extends Controller
{
    public function __construct(
        protected ProgressTrackingService $progressService,
        protected XPRewardService $xpService,
        protected MissionService $missionService
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
     * The most played time one request may report (the player sends a chunk every
     * ~30 s of playback and on pause / ended / tab hide / page leave).
     */
    public const MAX_SECONDS_PER_REQUEST = 120;

    /** Network / timestamp jitter allowed on top of the real elapsed time. */
    public const CLOCK_TOLERANCE_SECONDS = 2;

    /**
     * Log a chunk of actually played video time (seconds of playback, seeking and
     * skipping excluded on the client) and credit it to the lesson watch time, the
     * enrollment, student_profiles.total_watch_time and the "watch_time" daily mission.
     *
     * Server-side rules: a student can never be credited more than the real time that
     * passed since their previous credited chunk (across all lessons and tabs), at most
     * MAX_SECONDS_PER_REQUEST per request, and a retried (session_id, seq) is ignored.
     */
    public function logVideoWatch(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
            'seq' => ['required', 'integer', 'min:1', 'max:100000'],
            'duration' => ['required', 'integer', 'min:1', 'max:'.self::MAX_SECONDS_PER_REQUEST],
            'start_position' => ['nullable', 'integer', 'min:0'],
            'end_position' => ['nullable', 'integer', 'min:0'],
            'quality' => ['nullable', 'string', 'max:20'],
            'playback_rate' => ['nullable', 'numeric', 'min:0.25', 'max:2'],
        ]);

        $user = Auth::user();

        // only a lesson the student opened (enrolled: the lesson page creates the progress row)
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        abort_if($lesson->type === 'text' && !$lesson->video, 422, "Bu darsda video yo'q");

        // a retried request (lost response, double pagehide + visibilitychange) is counted once
        $dedupeKey = "video-watch:{$user->id}:{$data['session_id']}:{$data['seq']}";
        if (!Cache::add($dedupeKey, true, now()->addHours(12))) {
            return response()->json(['success' => true, 'credited' => 0, 'duplicate' => true]);
        }

        $credited = DB::transaction(function () use ($user, $lesson, $progress, $data, $request) {
            // serialises a student's concurrent requests (two tabs) so the elapsed-time cap holds
            $profile = StudentProfile::where('user_id', $user->id)->lockForUpdate()->first();

            $now = now();
            $claimed = (int) $data['duration'];
            $lastEndedAt = VideoWatchLog::where('user_id', $user->id)->max('ended_at');

            $allowed = $claimed;
            if ($lastEndedAt) {
                $elapsed = max(0, Carbon::parse($lastEndedAt)->diffInSeconds($now, false));
                $allowed = (int) min($claimed, floor($elapsed) + self::CLOCK_TOLERANCE_SECONDS);
            }

            if ($allowed <= 0) {
                return 0;
            }

            $total = (int) ($progress->total_duration ?: ($lesson->video?->duration ?? 0));
            $clamp = fn ($position) => $position === null ? null : ($total > 0 ? min((int) $position, $total) : (int) $position);
            $start = $clamp($data['start_position'] ?? null) ?? 0;
            $end = $clamp($data['end_position'] ?? null) ?? $start;

            VideoWatchLog::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'video_id' => $lesson->video?->id,
                'start_position' => $start,
                'end_position' => $end,
                'duration' => $allowed,
                'quality' => $data['quality'] ?? null,
                'playback_rate' => $data['playback_rate'] ?? 1.0,
                'device_type' => $this->detectDeviceType($request),
                'started_at' => $now->copy()->subSeconds($allowed),
                'ended_at' => $now,
            ]);

            $progress->watch_time = (int) $progress->watch_time + $allowed;
            if (($data['end_position'] ?? null) !== null) {
                // resume point for the next visit (completion stays with progress.update)
                $progress->last_position = $end;
            }
            $progress->save();

            $profile?->increment('total_watch_time', $allowed);
            $progress->enrollment?->increment('total_watch_time', $allowed);

            return $allowed;
        });

        if ($credited > 0) {
            // "30 daqiqa video tomosha qiling": the mission counts seconds (target 1800)
            $this->missionService->updateProgress($user, 'watch_time', $credited);
        }

        return response()->json([
            'success' => true,
            'credited' => $credited,
            'watch_time' => (int) $progress->fresh()->watch_time,
        ]);
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
