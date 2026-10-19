<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadRegistration;
use App\Services\AntiCheatService;
use App\Services\LeaderboardService;
use App\Services\OlympiadExamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\XPRewardService;

class OlympiadExamController extends Controller
{
    protected OlympiadExamService $examService;
    protected AntiCheatService $antiCheatService;
    protected LeaderboardService $leaderboardService;
    protected XPRewardService $xpService;

    public function __construct(
        OlympiadExamService $examService,
        AntiCheatService $antiCheatService,
        LeaderboardService $leaderboardService,
        XPRewardService $xpService
    ) {
        $this->examService = $examService;
        $this->antiCheatService = $antiCheatService;
        $this->leaderboardService = $leaderboardService;
        $this->xpService = $xpService;
    }

    /** Uzbek messages for OlympiadExamService::canStartExam() reasons. */
    private const REASONS = [
        'registration_not_confirmed' => "Ro'yxatdan o'tishingiz hali tasdiqlanmagan (to'lov kutilmoqda).",
        'already_completed' => 'Siz bu olimpiadani allaqachon topshirgansiz.',
        'disqualified' => 'Siz bu olimpiadadan chetlatilgansiz.',
        'olympiad_not_live' => 'Olimpiada hozir faol emas.',
        'olympiad_not_started' => 'Olimpiada hali boshlanmagan.',
        'olympiad_ended' => 'Olimpiada yakunlangan.',
    ];

    private function registrationFor(Olympiad $olympiad): ?OlympiadRegistration
    {
        return OlympiadRegistration::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    private function attemptFor(Olympiad $olympiad): ?OlympiadAttempt
    {
        return OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    /**
     * The attempt behind the X-Session-Token header (exam AJAX calls).
     */
    private function sessionAttempt(Request $request, Olympiad $olympiad): ?OlympiadAttempt
    {
        $attempt = $this->attemptFor($olympiad);

        if (! $attempt || ! hash_equals((string) $attempt->session_token, (string) $request->header('X-Session-Token'))) {
            return null;
        }

        return $attempt;
    }

    private function invalidSession()
    {
        return response()->json(['success' => false, 'error' => 'Invalid session', 'message' => "Imtihon sessiyasi topilmadi. Sahifani yangilang."], 401);
    }

    private function failure(\Throwable $e)
    {
        report($e);

        return response()->json(['success' => false, 'message' => "Amalni bajarib bo'lmadi. Sahifani yangilab, qayta urinib ko'ring."], 422);
    }

    /**
     * Device and readiness check before the exam.
     */
    public function preflight(string $slug)
    {
        $olympiad = Olympiad::with(['sections' => fn ($q) => $q->ordered()->withCount('questions')])
            ->where('slug', $slug)
            ->firstOrFail();

        $registration = $this->registrationFor($olympiad);
        if (! $registration) {
            return redirect()->route('student.olympiads.show', $slug)
                ->with('error', "Imtihonda qatnashish uchun avval ro'yxatdan o'ting.");
        }

        $check = $this->examService->canStartExam($registration);
        if (empty($check['can_continue']) && $olympiad->sections->sum('questions_count') === 0) {
            $check = ['can_start' => false, 'reason' => 'no_questions'];
        }

        return Inertia::render('Student/Olympiad/Exam/PreFlight', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'total_duration' => $olympiad->total_duration_minutes,
                'anti_cheat_config' => $olympiad->anti_cheat_config ?? [],
                'sections' => $olympiad->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'duration_minutes' => $s->duration_minutes,
                    'questions_count' => $s->questions_count,
                ]),
            ],
            'canStart' => [
                'can_start' => (bool) $check['can_start'],
                'can_continue' => ! empty($check['can_continue']),
                'reason' => $check['reason'] ?? null,
                'message' => isset($check['reason'])
                    ? (self::REASONS[$check['reason']] ?? "Bu olimpiadaga hali savollar qo'shilmagan.")
                    : null,
            ],
        ]);
    }

    /**
     * Start (or continue) the attempt — called by the pre-flight page.
     */
    public function start(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $request->validate([
            'device_fingerprint' => ['required', 'string', 'max:128'],
            'fingerprint_data' => ['nullable', 'array'],
        ]);

        $registration = $this->registrationFor($olympiad);
        if (! $registration) {
            return response()->json(['success' => false, 'message' => "Siz bu olimpiadaga ro'yxatdan o'tmagansiz."], 403);
        }

        $check = $this->examService->canStartExam($registration);
        if (! $check['can_start']) {
            return response()->json(['success' => false, 'message' => self::REASONS[$check['reason']] ?? 'Imtihonni boshlab bo\'lmaydi.'], 422);
        }

        try {
            $this->examService->startExam($registration, $request->device_fingerprint, (array) $request->input('fingerprint_data', []));
        } catch (\Throwable $e) {
            $message = str_contains($e->getMessage(), 'another device')
                ? "Imtihon boshqa qurilmada boshlangan. O'sha qurilmadan davom eting."
                : (str_contains($e->getMessage(), 'blocked') ? 'Bu qurilma bloklangan.' : null);

            return $message
                ? response()->json(['success' => false, 'message' => $message], 422)
                : $this->failure($e);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('student.olympiads.exam', $slug),
        ]);
    }

    /**
     * The exam page.
     */
    public function exam(string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $attempt = $this->attemptFor($olympiad);

        if (! $attempt || ! $attempt->is_in_progress) {
            return redirect()->route($attempt && $attempt->is_completed ? 'student.olympiads.results' : 'student.olympiads.preflight', $slug);
        }

        if ($attempt->isExpired()) {
            $this->examService->submitExam($attempt);

            return redirect()->route('student.olympiads.results', $slug)->with('info', 'Imtihon vaqti tugadi, javoblaringiz topshirildi.');
        }

        $state = $this->examService->getExamState($attempt);

        return Inertia::render('Student/Olympiad/Exam/Index', [
            'olympiad' => ['id' => $olympiad->id, 'title' => $olympiad->title, 'slug' => $olympiad->slug],
            'attempt' => [
                'id' => $attempt->id,
                'session_token' => $attempt->session_token,
                'current_section_id' => $attempt->current_section_id,
                'remaining_seconds' => $attempt->remaining_seconds,
            ],
            'sections' => $state['sections']->sortBy(fn ($sa) => $sa->section?->order_number)->map(fn ($sa) => [
                'id' => $sa->section_id,
                'title' => $sa->section?->title,
                'status' => $sa->status,
                'duration_minutes' => $sa->section?->duration_minutes,
            ])->values(),
            'progress' => $state['total_progress'],
        ]);
    }

    /**
     * Questions of a section (JSON), without anything that reveals the answer.
     */
    public function getSection(Request $request, string $slug, string $section)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $attempt = $this->sessionAttempt($request, $olympiad);
        if (! $attempt || ! $attempt->can_continue) {
            return $this->invalidSession();
        }

        try {
            $this->examService->startSection($attempt, $section);
            $data = $this->examService->getSectionQuestions($attempt->fresh(), $section);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }

        return response()->json([
            'questions' => $data['questions'],
            'remaining_time' => $data['remaining_time'],
        ]);
    }

    public function submitAnswer(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $attempt = $this->sessionAttempt($request, $olympiad);
        if (! $attempt) {
            return $this->invalidSession();
        }

        $data = $request->validate([
            'section_id' => ['required', 'uuid'],
            'question_id' => ['required', 'uuid'],
            'answer' => ['nullable'],
            'time_spent' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        try {
            $this->examService->submitAnswer($attempt, $data['section_id'], $data['question_id'], $data['answer'] ?? null, (int) ($data['time_spent'] ?? 0));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }

        return response()->json(['success' => true]);
    }

    public function toggleFlag(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $attempt = $this->sessionAttempt($request, $olympiad);
        if (! $attempt) {
            return $this->invalidSession();
        }

        $data = $request->validate(['question_id' => ['required', 'uuid']]);
        abort_unless(\App\Models\OlympiadQuestion::whereKey($data['question_id'])->where('olympiad_id', $olympiad->id)->exists(), 404);

        return response()->json([
            'success' => true,
            'is_flagged' => $this->examService->toggleFlag($attempt, $data['question_id']),
        ]);
    }

    public function completeSection(Request $request, string $slug, string $section)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $attempt = $this->sessionAttempt($request, $olympiad);
        if (! $attempt) {
            return $this->invalidSession();
        }

        try {
            $this->examService->completeSection($attempt, $section);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }

        $attempt->refresh();
        $next = (string) $attempt->current_section_id !== (string) $section ? $attempt->current_section_id : null;

        return response()->json(['success' => true, 'next_section_id' => $next]);
    }

    /**
     * Submit entire exam
     */
    public function submit(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($request->header('X-Session-Token') !== $attempt->session_token) {
            return response()->json(['error' => 'Invalid session'], 401);
        }

        try {
            $this->examService->submitExam($attempt);
            
            // Award participation XP
            $this->xpService->awardOlympiadXP(auth()->user(), 'participation');

            return response()->json([
                'success' => true,
                'redirect' => route('student.olympiads.results', $slug),
            ]);

        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * Report violation (AJAX)
     */
    public function reportViolation(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        $request->validate([
            'type' => 'required|string',
            'details' => 'nullable|array',
        ]);

        $attempt->recordViolation($request->type);

        return response()->json([
            'warnings_count' => $attempt->fresh()->warnings_count,
            'is_disqualified' => $attempt->is_disqualified,
        ]);
    }

    /**
     * Heartbeat (AJAX) - keep session alive
     */
    public function heartbeat(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attempt || $request->header('X-Session-Token') !== $attempt->session_token) {
            return response()->json(['error' => 'Invalid session'], 401);
        }

        // Check heartbeat gap
        $lastHeartbeat = $request->input('last_heartbeat', now()->timestamp);
        $this->antiCheatService->checkHeartbeat($attempt, $lastHeartbeat);

        return response()->json([
            'status' => $attempt->status,
            'remaining_seconds' => $attempt->remaining_seconds,
            'is_disqualified' => $attempt->is_disqualified,
            'server_time' => now()->timestamp,
        ]);
    }

    /**
     * Get live leaderboard (AJAX)
     */
    public function leaderboard(string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $leaderboard = $this->leaderboardService->getLiveLeaderboard($olympiad->id, 20);
        $userPosition = $this->leaderboardService->getUserPosition($olympiad->id, auth()->id());

        return response()->json([
            'leaderboard' => $leaderboard->map(fn($e) => [
                'rank' => $e->rank,
                'rank_change' => $e->rank_change,
                'user_name' => $e->user->name,
                'user_avatar' => $e->user->avatar,
                'score' => $e->score,
                'score_percent' => $e->score_percent,
            ]),
            'user_position' => $userPosition,
        ]);
    }
}
