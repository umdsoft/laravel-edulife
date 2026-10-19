<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadLiveLeaderboard;
use App\Models\SecurityViolation;
use App\Services\AntiCheatService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OlympiadMonitorController extends Controller
{
    protected LeaderboardService $leaderboardService;
    protected AntiCheatService $antiCheatService;

    public function __construct(
        LeaderboardService $leaderboardService,
        AntiCheatService $antiCheatService
    ) {
        $this->leaderboardService = $leaderboardService;
        $this->antiCheatService = $antiCheatService;
    }

    /**
     * Live monitoring dashboard
     */
    public function index(string $id): Response
    {
        $olympiad = Olympiad::with(['olympiadType', 'stage'])->findOrFail($id);

        $liveStats = $this->getLiveStats($olympiad);
        $topPerformers = $this->leaderboardService->getTopPerformers($olympiad->id, 10);
        $activeViolations = $this->antiCheatService->getActiveViolations($olympiad->id);

        return Inertia::render('Admin/Olympiad/Monitor', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'status' => $olympiad->status,
                'olympiad_start_at' => $olympiad->olympiad_start_at?->toIso8601String(),
                'olympiad_end_at' => $olympiad->olympiad_end_at?->toIso8601String(),
            ],
            'liveStats' => $liveStats,
            'topPerformers' => $topPerformers->map(fn($e) => [
                'attempt_id' => $e->attempt_id,
                'rank' => $e->rank,
                'user_name' => $e->user->name,
                'score' => $e->score,
                'score_percent' => round($e->score_percent, 1),
            ]),
            'activeViolations' => $activeViolations->map(fn($v) => [
                'id' => $v->id,
                'attempt_id' => $v->attempt_id,
                'user_name' => $v->user->name,
                'type' => $v->violation_label,
                'severity' => $v->severity,
                'count' => $v->occurrence_count,
                'action' => $v->action_taken,
                'created_at' => $v->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Get live statistics (AJAX)
     */
    public function stats(string $id)
    {
        $olympiad = Olympiad::findOrFail($id);
        return response()->json($this->getLiveStats($olympiad));
    }

    /**
     * Calculate live statistics
     */
    private function getLiveStats(Olympiad $olympiad): array
    {
        $totalRegistered = $olympiad->registrations()->confirmed()->count();
        $activeNow = $olympiad->attempts()
            ->where('status', OlympiadAttempt::STATUS_IN_PROGRESS)
            ->count();
        $completed = $olympiad->attempts()->completed()->count();
        $disqualified = $olympiad->attempts()->where('is_disqualified', true)->count();
        $activeViolations = SecurityViolation::where('olympiad_id', $olympiad->id)
            ->where('is_resolved', false)
            ->count();

        $avgScore = $olympiad->attempts()
            ->completed()
            ->notDisqualified()
            ->avg('score_percent') ?? 0;

        return [
            'total_registered' => $totalRegistered,
            'active_now' => $activeNow,
            'completed' => $completed,
            'disqualified' => $disqualified,
            'not_started' => $totalRegistered - $completed - $activeNow - $disqualified,
            'active_violations' => $activeViolations,
            'completion_rate' => $totalRegistered > 0 
                ? round(($completed / $totalRegistered) * 100) 
                : 0,
            'average_score' => round($avgScore, 1),
        ];
    }

    /**
     * Get active participants list
     */
    public function activeParticipants(string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        $participants = OlympiadAttempt::with(['user:id,first_name,last_name,email,avatar', 'deviceLock.device'])
            ->where('olympiad_id', $id)
            ->where('status', OlympiadAttempt::STATUS_IN_PROGRESS)
            ->get();

        return response()->json([
            'participants' => $participants->map(fn($a) => [
                'attempt_id' => $a->id,
                'user_id' => $a->user_id,
                'user_name' => $a->user->name,
                'user_avatar' => $a->user->avatar,
                'started_at' => $a->started_at->diffForHumans(),
                'elapsed_minutes' => $a->started_at->diffInMinutes(now()),
                'current_section' => $a->current_section_id,
                'warnings_count' => $a->warnings_count,
                'tab_switches' => $a->tab_switches,
                'device_ip' => $a->deviceLock?->locked_ip,
            ]),
        ]);
    }

    /**
     * Get live leaderboard
     */
    public function leaderboard(Request $request, string $id)
    {
        // "To'liq reyting" is a link: a browser gets the page, the monitor's fetch() gets JSON
        if (! $request->wantsJson()) {
            return $this->leaderboardPage($id);
        }

        $leaderboard = $this->leaderboardService->getLiveLeaderboard($id, 50);
        $stats = $this->leaderboardService->getStatistics($id);

        return response()->json([
            'leaderboard' => $leaderboard->map(fn($e) => [
                'rank' => $e->rank,
                'rank_change' => $e->rank_change,
                'user_id' => $e->user_id,
                'user_name' => $e->user->name,
                'score' => $e->score,
                'score_percent' => round($e->score_percent, 1),
                'questions_answered' => $e->questions_answered,
                'questions_correct' => $e->questions_correct,
                'time_spent' => $e->formatted_time,
                'is_disqualified' => $e->is_disqualified,
            ]),
            'statistics' => $stats,
        ]);
    }

    /**
     * Every participant in place order (disqualified ones last), with the
     * same numbers the monitor shows.
     */
    private function leaderboardPage(string $id): Response
    {
        $olympiad = Olympiad::findOrFail($id);

        $entries = OlympiadLiveLeaderboard::with(['user:id,first_name,last_name'])
            ->where('olympiad_id', $olympiad->id)
            ->orderBy('is_disqualified')
            ->orderByRaw('CASE WHEN `rank` IS NULL OR `rank` = 0 THEN 1 ELSE 0 END')
            ->orderBy('rank')
            ->get();

        return Inertia::render('Admin/Olympiad/Leaderboard', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'status' => $olympiad->status,
            ],
            'leaderboard' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'rank' => $e->is_disqualified ? null : $e->rank,
                'attempt_id' => $e->attempt_id,
                'user_name' => $e->user?->name ?? '—',
                'score' => $e->score,
                'max_score' => $e->max_score,
                'score_percent' => round((float) $e->score_percent, 1),
                'questions_answered' => $e->questions_answered,
                'questions_correct' => $e->questions_correct,
                'time_spent' => $e->formatted_time,
                'is_disqualified' => (bool) $e->is_disqualified,
            ])->values(),
            'statistics' => $this->leaderboardService->getStatistics($olympiad->id),
        ]);
    }

    /**
     * View participant details
     */
    public function participantDetails(string $id, string $attemptId): Response
    {
        $olympiad = Olympiad::findOrFail($id);
        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)->with([
            'user',
            'sectionAttempts.section',
            'deviceLock.device',
            'violations',
        ])->findOrFail($attemptId);

        return Inertia::render('Admin/Olympiad/ParticipantDetails', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                    'avatar' => $attempt->user->avatar,
                ],
                'status' => $attempt->status,
                'started_at' => $attempt->started_at?->format('d.m.Y H:i:s'),
                'completed_at' => $attempt->completed_at?->format('d.m.Y H:i:s'),
                'duration_seconds' => (int) ($attempt->total_duration_seconds
                    ?: ($attempt->started_at ? $attempt->started_at->diffInSeconds($attempt->completed_at ?? now()) : 0)),
                'score' => $attempt->total_raw_score,
                'score_percent' => round((float) $attempt->score_percent, 1),
                'max_score' => (float) $attempt->total_max_score,
                'rank' => $attempt->rank,
                'warnings_count' => $attempt->warnings_count,
                'tab_switches' => $attempt->tab_switches,
                'fullscreen_exits' => $attempt->fullscreen_exits,
                'is_disqualified' => $attempt->is_disqualified,
                'disqualified_reason' => $attempt->disqualified_reason,
            ],
            'device' => $attempt->deviceLock?->device ? [
                'os' => $attempt->deviceLock->device->os_name,
                'browser' => $attempt->deviceLock->device->browser_name,
                'screen' => $attempt->deviceLock->device->screen_resolution,
                'ip' => $attempt->deviceLock->locked_ip,
                'trust_score' => $attempt->deviceLock->device->trust_score,
            ] : null,
            'sections' => $attempt->sectionAttempts->map(fn($s) => [
                'section_title' => $s->section->title,
                'status' => $s->status,
                'score' => $s->raw_score,
                'max_score' => $s->max_score,
                'questions_answered' => $s->questions_answered,
            ]),
            'violations' => $attempt->violations->map(fn($v) => [
                'type' => $v->violation_label,
                'severity' => $v->severity,
                'count' => $v->occurrence_count,
                'action' => $v->action_taken,
                'created_at' => $v->created_at->format('H:i:s'),
            ]),
        ]);
    }

    /**
     * Force submit participant's exam
     */
    public function forceSubmit(Request $request, string $id, string $attemptId)
    {
        $attempt = OlympiadAttempt::where('olympiad_id', $id)
            ->findOrFail($attemptId);

        if ($attempt->is_completed) {
            return back()->with('error', 'Imtihon allaqachon yakunlangan');
        }

        $attempt->submit();

        return back()->with('success', 'Imtihon majburiy yakunlandi');
    }

    /**
     * Disqualify participant
     */
    public function disqualify(Request $request, string $id, string $attemptId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $attempt = OlympiadAttempt::where('olympiad_id', $id)
            ->findOrFail($attemptId);

        $attempt->disqualify($request->reason);

        return back()->with('success', 'Ishtirokchi diskvalifikatsiya qilindi');
    }

    /**
     * Reinstate disqualified participant
     */
    public function reinstate(Request $request, string $id, string $attemptId)
    {
        $attempt = OlympiadAttempt::where('olympiad_id', $id)
            ->findOrFail($attemptId);

        if (!$attempt->is_disqualified) {
            return back()->with('error', 'Ishtirokchi diskvalifikatsiya qilinmagan');
        }

        $attempt->is_disqualified = false;
        $attempt->disqualified_reason = null;
        $attempt->save();

        // Update leaderboard
        $attempt->updateLeaderboard();

        return back()->with('success', 'Ishtirokchi qayta tiklandi');
    }
}
