<?php

namespace App\Http\Controllers\Student\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Exceptions\Exam\ExamUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Exam\ExamAccess;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamPlacementService;
use App\Services\Exam\ExamSessionLifecycle;
use App\Services\Payments\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Shanba imtihoni" home of a student: placement test per subject, the
 * next Saturday session (countdown), history; and the two start actions.
 */
class ExamController extends Controller
{
    public function __construct(
        private readonly ExamPlacementService $placement,
        private readonly ExamSessionLifecycle $lifecycle,
        private readonly ExamAttemptService $attempts,
        private readonly ExamAccess $access,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $profile = StudentExamProfile::query()->where('user_id', $user->id)->first();
        if ($redirect = $this->onboardingRedirect($profile)) {
            return $redirect;
        }

        $subjects = Subject::query()->active()
            ->when($profile && ! empty($profile->subjects), fn ($q) => $q->whereIn('code', $profile->subjects))
            ->orderBy('sort_order')
            ->get();

        $placement = $subjects->map(fn (Subject $subject) => $this->placementCard($user, $subject))->values();

        $upcoming = ExamSession::query()
            ->public()
            ->whereIn('type', [ExamSessionType::Weekly->value, ExamSessionType::OpenRegional->value])
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('subject')
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->each(fn (ExamSession $s) => $this->lifecycle->sync($s))
            ->filter(fn (ExamSession $s) => in_array($s->status, [ExamSessionStatus::Scheduled, ExamSessionStatus::Open], true))
            ->values();

        $mine = ExamAttempt::query()->where('user_id', $user->id)->whereIn('session_id', $upcoming->pluck('id'))->get()->keyBy('session_id');

        $history = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', AttemptStatus::InProgress->value)
            ->with(['session' => fn ($q) => $q->withTrashed()->with('subject')])
            ->latest('started_at')
            ->limit(30)
            ->get();
        $weekly = StudentWeeklyResult::query()->whereIn('attempt_id', $history->pluck('id'))->get()->keyBy('attempt_id');

        $running = ExamAttempt::query()->where('user_id', $user->id)->inProgress()
            ->where('expires_at', '>', now())->with('session')->latest('started_at')->first();

        return Inertia::render('Student/Exam/Index', [
            'placement' => $placement,
            'upcoming' => $upcoming->map(fn (ExamSession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'type' => $s->type?->value,
                'status' => $s->status?->value,
                'starts_at' => $s->starts_at?->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'duration_min' => $s->effectiveDurationMin(),
                'subject' => ['code' => $s->subject?->code, 'name' => $s->subject?->name_uz],
                'attempt' => ($a = $mine->get($s->id)) ? ['id' => $a->id, 'status' => $a->status?->value] : null,
                // paid exams: free | subscription | purchased | pay (+ price, can_buy)
                'access' => $this->access->state($s, $user),
            ]),
            'history' => $history->map(fn (ExamAttempt $a) => [
                'id' => $a->id,
                'title' => $a->session?->title,
                'type' => $a->session?->type?->value,
                'subject' => ['code' => $a->session?->subject?->code, 'name' => $a->session?->subject?->name_uz],
                'status' => $a->status?->value,
                'score' => (float) $a->score,
                'max_score' => (float) $a->max_score,
                'score_percent' => (float) $a->score_percent,
                'submitted_at' => $a->submitted_at?->toIso8601String(),
                'delta_prev' => ($w = $weekly->get($a->id)) && $w->delta_prev !== null ? (float) $w->delta_prev : null,
            ]),
            'running' => $running ? ['id' => $running->id, 'title' => $running->session?->title, 'expires_at' => $running->expires_at?->toIso8601String()] : null,
            'serverNow' => now()->toIso8601String(),
            'payments' => $this->paymentsProps($user),
            'paymentNotice' => $this->paymentNotice($request),
            'needsOnboarding' => ! $profile || ! $profile->isComplete(),
            'links' => [
                'progress' => Route::has('student.exam.progress') ? route('student.exam.progress') : null,
                'leaderboard' => Route::has('student.exam.leaderboard') ? route('student.exam.leaderboard') : null,
                'onboarding' => Route::has('student.exam.onboarding') ? route('student.exam.onboarding') : null,
            ],
        ]);
    }

    public function startPlacement(Request $request, Subject $subject): RedirectResponse
    {
        $user = $request->user();
        if ($redirect = $this->onboardingRedirect(StudentExamProfile::query()->where('user_id', $user->id)->first())) {
            return $redirect;
        }

        $session = $this->placement->sessionFor($user, $subject);
        if (! $session) {
            $latest = $this->placement->latestAttempt($user, $subject);

            return $latest
                ? redirect()->route('student.exam.result', $latest)->with('info', ExamUnavailable::placementDone()->getMessage())
                : back()->with('error', 'Bu fan bo\'yicha qabul testi hali tayyor emas.');
        }

        return $this->start($request, $session);
    }

    public function startSession(Request $request, ExamSession $session): RedirectResponse
    {
        if ($session->type === ExamSessionType::Placement) {
            abort(404);
        }
        if ($redirect = $this->onboardingRedirect(StudentExamProfile::query()->where('user_id', $request->user()->id)->first())) {
            return $redirect;
        }

        return $this->start($request, $this->lifecycle->sync($session));
    }

    private function start(Request $request, ExamSession $session): RedirectResponse
    {
        try {
            $attempt = $this->attempts->start($session, $request->user(), [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'fingerprint' => $request->input('fingerprint'),
            ]);
        } catch (ExamUnavailable $e) {
            $done = ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $request->user()->id)->first();
            if ($done && $e->reason === 'already_done') {
                return redirect()->route('student.exam.result', $done);
            }

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('student.exam.take', $attempt);
    }

    /** Paid exams: providers, which plans include exams, the student's own subscription with exams. */
    private function paymentsProps(User $user): array
    {
        $subscription = $this->access->subscriptionWithExams($user);

        return [
            'providers' => CheckoutService::enabledProviders(),
            'plans_url' => Route::has('student.subscriptions.index') ? route('student.subscriptions.index') : null,
            'exam_plans' => $this->access->examPlanNames(),
            'subscription' => $subscription ? [
                'plan' => $subscription->plan?->name,
                'ends_at' => $subscription->ends_at?->format('d.m.Y'),
            ] : null,
        ];
    }

    /**
     * Paid exams: the provider sends the student back to /student/exam?paid=<order uuid>;
     * the callback may arrive a few seconds later, the page refreshes until it does.
     */
    private function paymentNotice(Request $request): ?array
    {
        $uuid = (string) $request->query('paid', '');
        if ($uuid === '' || ! \Illuminate\Support\Str::isUuid($uuid)) {
            return null;
        }

        $order = PaymentOrder::query()->with('transactions')
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->where('product_type', PaymentOrder::TYPE_EXAM_SESSION)
            ->first();
        if (! $order) {
            return null;
        }

        return [
            'status' => $order->publicStatus(),
            'title' => preg_replace('/^Imtihon: /u', '', (string) $order->title),
            'amount' => (float) $order->amount,
            'status_url' => route('payments.return', $order),
            'retry_url' => $order->isPending() && in_array($order->provider, CheckoutService::enabledProviders(), true)
                ? route('payments.pay', $order) : null,
        ];
    }

    private function placementCard(User $user, Subject $subject): array
    {
        $latest = $this->placement->latestAttempt($user, $subject);
        $session = $this->placement->sessionFor($user, $subject);
        $retake = $session?->restricted_to_user_id !== null;

        $state = match (true) {
            $latest?->status === AttemptStatus::InProgress => 'in_progress',
            $session !== null => $retake ? 'retake' : 'available',
            $latest !== null => 'done',
            default => 'unavailable',
        };

        return [
            'subject' => ['id' => $subject->id, 'code' => $subject->code, 'name' => $subject->name_uz],
            'state' => $state,
            'duration_min' => $session?->effectiveDurationMin(),
            'questions' => $session ? (int) $session->blueprint()->value('total_questions') : null,
            'attempt' => $latest ? [
                'id' => $latest->id,
                'status' => $latest->status?->value,
                'score_percent' => (float) $latest->score_percent,
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
            ] : null,
        ];
    }

    private function onboardingRedirect(?StudentExamProfile $profile): ?RedirectResponse
    {
        $complete = $profile && $profile->isComplete();
        if ($complete || ! Route::has('student.exam.onboarding')) {
            return null;
        }

        return redirect()->route('student.exam.onboarding')
            ->with('info', 'Imtihonlarda qatnashish uchun avval qisqa anketani to\'ldiring.');
    }
}
