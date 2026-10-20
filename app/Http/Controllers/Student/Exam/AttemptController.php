<?php

namespace App\Http\Controllers\Student\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionType;
use App\Exceptions\Exam\ExamUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Services\Exam\ExamAttemptPresenter;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamSessionLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A running attempt (Take page, autosave, anti-cheat log, submit) and its
 * result page. Only the attempt's own student gets in.
 */
class AttemptController extends Controller
{
    private const EVENT_TYPES = ['tab_switch', 'window_blur', 'fullscreen_exit', 'copy', 'paste', 'context_menu'];

    public function __construct(
        private readonly ExamAttemptService $attempts,
        private readonly ExamSessionLifecycle $lifecycle,
        private readonly ExamAttemptPresenter $presenter,
    ) {}

    public function take(Request $request, ExamAttempt $attempt): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);
        $session = $this->sessionOf($attempt);
        $this->lifecycle->sync($session);
        $attempt->refresh();

        if ($attempt->status === AttemptStatus::InProgress && $attempt->isExpired()) {
            $this->attempts->submit($attempt, true);
        }
        if ($attempt->status !== AttemptStatus::InProgress) {
            return redirect()->route('student.exam.result', $attempt);
        }

        return Inertia::render('Student/Exam/Take', [
            'attempt' => [
                'id' => $attempt->id,
                'token' => $attempt->session_token,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'expires_at' => $attempt->expires_at?->toIso8601String(),
                'remaining_seconds' => $this->attempts->remainingSeconds($attempt),
                'violations_count' => $attempt->violations_count,
            ],
            'session' => $this->presenter->session($session),
            'questions' => $this->presenter->questions($attempt),
            'answers' => (object) $this->presenter->savedAnswers($attempt),
            'autosaveSeconds' => (int) config('exam.engine.autosave_seconds', 15),
            'serverNow' => now()->toIso8601String(),
        ]);
    }

    public function saveAnswers(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $attempt);
        $this->checkToken($request, $attempt);

        $request->validate([
            'answers' => ['required', 'array', 'max:'.(int) config('exam.engine.max_batch_answers', 100)],
            'answers.*.question_id' => ['required', 'string', 'max:64'],
            'answers.*.selected_option_id' => ['nullable', 'string', 'max:64'],
            'answers.*.selected_option_ids' => ['nullable', 'array', 'max:10'],
            'answers.*.selected_option_ids.*' => ['string', 'max:64'],
            'answers.*.answer_text' => ['nullable', 'string', 'max:64'],
            'answers.*.unit' => ['nullable', 'string', 'max:30'],
            'answers.*.is_flagged' => ['nullable', 'boolean'],
            'answers.*.time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        // keep only the keys the client actually sent (absent != null)
        $items = array_map(fn ($i) => array_intersect_key($i, array_flip([
            'question_id', 'selected_option_id', 'selected_option_ids', 'answer_text', 'unit', 'is_flagged', 'time_spent_seconds',
        ])), $request->input('answers', []));

        try {
            $result = $this->attempts->saveAnswers($attempt, $items);
        } catch (ExamUnavailable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'reason' => $e->reason,
                'redirect' => route('student.exam.result', $attempt),
            ], 409);
        }

        return response()->json($result + ['server_now' => now()->toIso8601String()]);
    }

    public function logEvent(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $attempt);
        $this->checkToken($request, $attempt);
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', self::EVENT_TYPES)],
            'hidden_ms' => ['nullable', 'integer', 'min:0', 'max:86400000'],
        ]);

        $this->attempts->logEvent($attempt, $data['type'], array_filter(['hidden_ms' => $data['hidden_ms'] ?? null]));

        return response()->json(['violations_count' => $attempt->fresh()->violations_count]);
    }

    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);
        $this->attempts->submit($attempt);

        return redirect()->route('student.exam.result', $attempt)->with('success', 'Javoblaringiz qabul qilindi.');
    }

    public function result(Request $request, ExamAttempt $attempt): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);
        $session = $this->sessionOf($attempt);
        $this->lifecycle->sync($session);
        $attempt->refresh();

        if ($attempt->status === AttemptStatus::InProgress) {
            if (! $attempt->isExpired()) {
                return redirect()->route('student.exam.take', $attempt);
            }
            $this->attempts->submit($attempt, true);
        }

        $reviewAvailable = $this->presenter->reviewAvailable($attempt, $session);

        return Inertia::render('Student/Exam/Result', [
            'attempt' => $this->presenter->summary($attempt),
            'session' => $this->presenter->session($session),
            'weekly' => $session->type === ExamSessionType::Weekly ? $this->presenter->weekly($attempt) : null,
            'topics' => $attempt->status === AttemptStatus::Graded ? $this->presenter->topics($attempt) : [],
            'reviewAvailable' => $reviewAvailable,
            'review' => $reviewAvailable ? $this->presenter->review($attempt) : [],
            'links' => [
                'progress' => Route::has('student.exam.progress') ? route('student.exam.progress') : null,
                // stage 7 result card (PNG) of this attempt's weekly result
                'card' => Route::has('student.exam.card') && $session->type === ExamSessionType::Weekly && $attempt->status === AttemptStatus::Graded
                    ? route('student.exam.card', ['attempt' => $attempt->id, 'download' => 1])
                    : null,
            ],
        ]);
    }

    private function authorizeOwner(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }

    private function checkToken(Request $request, ExamAttempt $attempt): void
    {
        $token = (string) $request->header('X-Exam-Token', '');
        abort_unless($attempt->session_token !== null && hash_equals($attempt->session_token, $token), 403, 'Imtihon sessiyasi tasdiqlanmadi.');
    }

    private function sessionOf(ExamAttempt $attempt): ExamSession
    {
        return ExamSession::withTrashed()->with('subject')->findOrFail($attempt->session_id);
    }
}
