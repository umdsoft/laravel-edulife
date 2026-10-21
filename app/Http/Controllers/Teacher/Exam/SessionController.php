<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Http\Controllers\Controller;
use App\Jobs\Exam\GenerateExamVariantsJob;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\User;
use App\Services\Exam\ExamAccess;
use App\Services\Exam\ExamAudience;
use App\Services\Exam\ExamPlacementService;
use App\Services\Exam\ExamReadinessChecker;
use App\Services\Exam\ExamSessionLifecycle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exam sessions (Saturday exams): schedule, bank sufficiency before
 * opening, variant pre-generation, live participation, results + CSV.
 */
class SessionController extends Controller
{
    /** Audiences up to this size are generated in the request, larger ones on the queue. */
    private const INLINE_GENERATION_LIMIT = 200;

    public function __construct(
        private readonly ExamSessionLifecycle $lifecycle,
        private readonly ExamReadinessChecker $readiness,
        private readonly ExamAudience $audience,
        private readonly ExamPlacementService $placement,
    ) {}

    public function index(Request $request): Response
    {
        $tab = $request->query('tab') === 'placement' ? 'placement' : ($request->query('tab') === 'past' ? 'past' : 'upcoming');

        $query = ExamSession::query()->with(['subject', 'blueprint' => fn ($q) => $q->withTrashed()])
            ->withCount([
                'variants',
                'attempts',
                'attempts as finished_count' => fn ($q) => $q->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::AutoSubmitted->value, AttemptStatus::Graded->value]),
                // paid exams: who got in how
                'purchases as purchases_count' => fn ($q) => $q->where('status', ExamSessionPurchase::STATUS_PAID),
                'attempts as via_purchase_count' => fn ($q) => $q->where('access_via', ExamAccess::VIA_PURCHASE),
                'attempts as via_subscription_count' => fn ($q) => $q->where('access_via', ExamAccess::VIA_SUBSCRIPTION),
            ]);

        match ($tab) {
            'placement' => $query->where('type', ExamSessionType::Placement->value)->orderByDesc('created_at'),
            'past' => $query->where('type', '!=', ExamSessionType::Placement->value)
                ->whereIn('status', [ExamSessionStatus::Closed->value, ExamSessionStatus::Graded->value, ExamSessionStatus::Cancelled->value])
                ->orderByDesc('starts_at'),
            default => $query->where('type', '!=', ExamSessionType::Placement->value)
                ->whereIn('status', [ExamSessionStatus::Draft->value, ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
                ->orderBy('starts_at'),
        };

        $sessions = $query->paginate(20)->withQueryString();
        $sessions->getCollection()->each(fn (ExamSession $s) => $this->lifecycle->sync($s));

        return Inertia::render('Teacher/Exam/Sessions/Index', [
            'tab' => $tab,
            'sessions' => $sessions->through(fn (ExamSession $s) => $this->row($s)),
            'counts' => [
                'upcoming' => ExamSession::query()->where('type', '!=', ExamSessionType::Placement->value)
                    ->whereIn('status', [ExamSessionStatus::Draft->value, ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $default = (array) config('exam.engine.default_session', []);
        $saturday = now()->isSaturday() && now()->format('H:i') < ($default['start'] ?? '10:00') ? now() : now()->next(Carbon::SATURDAY);
        $subject = Subject::query()->active()->orderBy('sort_order')->first();

        return Inertia::render('Teacher/Exam/Sessions/Form', $this->formProps() + [
            'session' => [
                'id' => null,
                'subject_id' => $subject?->id,
                'blueprint_id' => null,
                'type' => 'weekly',
                'title' => 'Shanba imtihoni — '.$saturday->format('d.m.Y'),
                'date' => $saturday->toDateString(),
                'start_time' => $default['start'] ?? '10:00',
                'end_time' => $default['end'] ?? '13:00',
                'duration_min' => null,
                'price' => 0,
                'status' => 'draft',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $session = ExamSession::create($data + ['created_by' => $request->user()->id, 'status' => ExamSessionStatus::Draft]);

        return redirect()->route('teacher.exam.sessions.show', $session)
            ->with('success', 'Imtihon yaratildi. Bank yetarliligini tekshirib, rejalashtiring.');
    }

    public function show(ExamSession $session): Response
    {
        $this->lifecycle->sync($session);
        $session->load(['subject', 'blueprint' => fn ($q) => $q->withTrashed()]);

        $attempts = ExamAttempt::query()->where('session_id', $session->id)
            ->with('user:id,first_name,last_name,phone')
            ->orderByDesc('score_percent')
            ->orderBy('submitted_at')
            ->get();
        $weekly = StudentWeeklyResult::query()->where('session_id', $session->id)->get()->keyBy('attempt_id');
        $restricted = $session->restricted_to_user_id ? User::query()->find($session->restricted_to_user_id, ['id', 'first_name', 'last_name']) : null;

        return Inertia::render('Teacher/Exam/Sessions/Show', [
            'session' => $this->row($session) + [
                'registration_closes_at' => $session->registration_closes_at?->toIso8601String(),
                'restricted_to' => $restricted ? trim($restricted->first_name.' '.$restricted->last_name) : null,
                'blueprint' => $session->blueprint ? [
                    'id' => $session->blueprint->id,
                    'name' => $session->blueprint->name,
                    'total_questions' => $session->blueprint->total_questions,
                    'duration_min' => $session->blueprint->duration_min,
                    'trashed' => $session->blueprint->trashed(),
                ] : null,
            ],
            'readiness' => $this->readiness->forSession($session),
            'stats' => $this->stats($session),
            'payments' => $this->paymentStats($session),
            'results' => $attempts->map(fn (ExamAttempt $a) => $this->resultRow($a, $weekly->get($a->id)))->values(),
            'canEdit' => in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled], true),
            'canRetake' => $session->type === ExamSessionType::Placement,
        ]);
    }

    public function edit(ExamSession $session): Response|RedirectResponse
    {
        if (! in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled], true) || $session->type === ExamSessionType::Placement) {
            return redirect()->route('teacher.exam.sessions.show', $session)->with('error', 'Boshlangan imtihonni tahrirlab bo\'lmaydi.');
        }

        return Inertia::render('Teacher/Exam/Sessions/Form', $this->formProps() + [
            'session' => [
                'id' => $session->id,
                'subject_id' => $session->subject_id,
                'blueprint_id' => $session->blueprint_id,
                'type' => $session->type?->value,
                'title' => $session->title,
                'date' => $session->starts_at?->toDateString(),
                'start_time' => $session->starts_at?->format('H:i'),
                'end_time' => $session->ends_at?->format('H:i'),
                'duration_min' => $session->duration_min,
                'price' => (int) $session->price,
                'paid_count' => $session->purchases()->where('status', ExamSessionPurchase::STATUS_PAID)->count(),
                'status' => $session->status?->value,
                'has_variants' => $session->variants()->exists(),
            ],
        ]);
    }

    public function update(Request $request, ExamSession $session): RedirectResponse
    {
        if (! in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled], true) || $session->type === ExamSessionType::Placement) {
            return back()->with('error', 'Boshlangan imtihonni tahrirlab bo\'lmaydi.');
        }

        $data = $this->validated($request);
        // paid exams: what the students paid stays the price of the session
        if (array_key_exists('price', $data) && $data['price'] !== (int) $session->price && $session->purchases()->where('status', ExamSessionPurchase::STATUS_PAID)->exists()) {
            throw ValidationException::withMessages(['price' => 'To\'laganlar bor: narxni o\'zgartirib bo\'lmaydi.']);
        }
        if ($data['blueprint_id'] !== $session->blueprint_id && $session->variants()->exists()) {
            if ($session->attempts()->exists()) {
                throw ValidationException::withMessages(['blueprint_id' => 'Urinishlar bor: rejani almashtirib bo\'lmaydi.']);
            }
            // variants of the old blueprint are dropped and generated again
            $session->variants()->delete();
            $data['variants_generated_at'] = null;
        }
        $session->update($data);

        return redirect()->route('teacher.exam.sessions.show', $session)->with('success', 'Imtihon yangilandi.');
    }

    /** draft -> scheduled, after the bank sufficiency check. */
    public function schedule(Request $request, ExamSession $session): RedirectResponse
    {
        if ($session->status !== ExamSessionStatus::Draft) {
            return back()->with('error', 'Faqat qoralama imtihonni rejalashtirish mumkin.');
        }
        if ($session->starts_at === null || $session->ends_at === null || $session->ends_at->isPast()) {
            return back()->with('error', 'Imtihon vaqtini to\'g\'ri belgilang.');
        }

        $bank = $this->readiness->forSession($session)['bank'];
        if (! $bank['consistent']) {
            return back()->with('error', 'Rejadagi savollar soni qoidalar yig\'indisiga teng emas. Rejani saqlab qayta urinib ko\'ring.');
        }
        if ($bank['status'] === 'short' && ! $request->boolean('force')) {
            return back()->with('error', 'Savollar banki yetarli emas. Baribir rejalashtirish uchun tasdiqlang.');
        }

        $session->forceFill(['status' => ExamSessionStatus::Scheduled])->save();
        $this->lifecycle->sync($session);

        return back()->with('success', 'Imtihon rejalashtirildi. Variantlar boshlanishdan oldin avtomatik yaratiladi.');
    }

    public function cancel(ExamSession $session): RedirectResponse
    {
        $paid = $session->purchases()->where('status', ExamSessionPurchase::STATUS_PAID)->count();
        $this->lifecycle->cancel($session);

        return back()->with('success', $paid > 0
            ? "Imtihon bekor qilindi. {$paid} ta to'lov qaytarish uchun administratorga yuborildi."
            : 'Imtihon bekor qilindi.');
    }

    /** Close an open session now (auto-submits running attempts). */
    public function close(ExamSession $session): RedirectResponse
    {
        if ($session->status !== ExamSessionStatus::Open || $session->type === ExamSessionType::Placement) {
            return back()->with('error', 'Faqat ochiq imtihonni yopish mumkin.');
        }
        $this->lifecycle->close($session);

        return back()->with('success', 'Imtihon yopildi, natijalar e\'lon qilindi.');
    }

    public function generateVariants(ExamSession $session): RedirectResponse
    {
        if (! in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled, ExamSessionStatus::Open], true)) {
            return back()->with('error', 'Yakunlangan imtihon uchun variant yaratilmaydi.');
        }

        if ($this->audience->count($session) <= self::INLINE_GENERATION_LIMIT) {
            GenerateExamVariantsJob::dispatchSync($session->id);

            return back()->with('success', 'Variantlar yaratildi.');
        }

        GenerateExamVariantsJob::dispatch($session->id);

        return back()->with('success', 'Variantlar navbatga qo\'yildi. Bir necha daqiqada tayyor bo\'ladi.');
    }

    /** Polled by the session page while the exam runs. */
    public function live(ExamSession $session): JsonResponse
    {
        $this->lifecycle->sync($session);

        return response()->json(['status' => $session->status?->value] + $this->stats($session));
    }

    public function export(ExamSession $session): StreamedResponse
    {
        $attempts = ExamAttempt::query()->where('session_id', $session->id)
            ->with('user:id,first_name,last_name')
            ->orderByDesc('score_percent')->orderBy('submitted_at')->get();
        $weekly = StudentWeeklyResult::query()->where('session_id', $session->id)->get()->keyBy('attempt_id');
        $name = 'imtihon-'.($session->starts_at?->format('Y-m-d') ?? $session->created_at?->format('Y-m-d')).'-natijalar.csv';

        return response()->streamDownload(function () use ($attempts, $weekly) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel: UTF-8
            fputcsv($out, ["O'rin", "O'quvchi", 'Holat', 'Ball', 'Maks', 'Foiz', "To'g'ri", 'Xato', 'Javobsiz', 'Boshladi', 'Topshirdi', 'Vaqt (daq)', 'Qoidabuzarlik', "O'tgan haftaga nisbatan"], ';');
            foreach ($attempts as $a) {
                $row = $this->resultRow($a, $weekly->get($a->id));
                fputcsv($out, [
                    $row['rank'] ?? '',
                    $row['student'],
                    $row['status_label'],
                    $this->num($row['score']),
                    $this->num($row['max_score']),
                    $this->num($row['score_percent']),
                    $row['correct_count'],
                    $row['wrong_count'],
                    $row['skipped_count'],
                    $a->started_at?->format('d.m.Y H:i'),
                    $a->submitted_at?->format('d.m.Y H:i'),
                    $a->time_spent_seconds !== null ? (int) round($a->time_spent_seconds / 60) : '',
                    $row['violations_count'],
                    $row['delta_prev'] === null ? '' : $this->num($row['delta_prev']),
                ], ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Placement retake allowed by the teacher (private session for the student). */
    public function retake(Request $request, ExamSession $session, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->session_id === $session->id && $session->type === ExamSessionType::Placement, 404);

        $student = User::query()->findOrFail($attempt->user_id);
        $this->placement->allowRetake($student, Subject::query()->findOrFail($session->subject_id), $request->user());

        return back()->with('success', 'Qayta topshirishga ruxsat berildi.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', 'exists:exam_subjects,id'],
            'blueprint_id' => ['required', 'uuid'],
            'type' => ['required', Rule::in([ExamSessionType::Weekly->value, ExamSessionType::OpenRegional->value])],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'duration_min' => ['nullable', 'integer', 'min:5', 'max:300'],
            // paid exams: UZS; 0 = free; Payme / Click take at least 1 000 so'm
            'price' => ['nullable', 'integer', 'min:0', 'max:10000000', function ($attribute, $value, $fail) {
                if ((int) $value > 0 && (int) $value < 1000) {
                    $fail("Narx 0 (bepul) yoki kamida 1 000 so'm bo'lsin.");
                }
            }],
        ], [], [
            'title' => 'nomi',
            'date' => 'sana',
            'start_time' => 'boshlanish vaqti',
            'end_time' => 'tugash vaqti',
            'duration_min' => 'davomiyligi',
            'price' => 'narxi',
        ]);

        $blueprint = ExamBlueprint::query()->whereKey($data['blueprint_id'])->where('subject_id', $data['subject_id'])->first();
        if (! $blueprint || $blueprint->purpose === ExamSessionType::Placement) {
            throw ValidationException::withMessages(['blueprint_id' => 'Shu fanning haftalik imtihon rejasini tanlang.']);
        }

        $starts = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['start_time']);
        $ends = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['end_time']);
        if ($ends->lte($starts)) {
            throw ValidationException::withMessages(['end_time' => 'Tugash vaqti boshlanishdan keyin bo\'lishi kerak.']);
        }
        $duration = (int) ($data['duration_min'] ?? $blueprint->duration_min);
        if ($duration > $starts->diffInMinutes($ends)) {
            throw ValidationException::withMessages(['duration_min' => 'Davomiylik imtihon oynasidan uzun bo\'lmasin.']);
        }

        return [
            'subject_id' => $data['subject_id'],
            'blueprint_id' => $blueprint->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'starts_at' => $starts,
            'ends_at' => $ends,
            'duration_min' => $data['duration_min'] ?? null,
            // a form without the field (older page) keeps the price as it is
            ...($request->has('price') ? ['price' => (int) ($data['price'] ?? 0)] : []),
        ];
    }

    /** Paid exams: price, entries bought, who got in how, refunds. */
    private function paymentStats(ExamSession $session): array
    {
        $purchases = ExamSessionPurchase::query()->where('session_id', $session->id)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $via = ExamAttempt::query()->where('session_id', $session->id)
            ->selectRaw('access_via, count(*) as c')->groupBy('access_via')->pluck('c', 'access_via');

        return [
            'price' => $session->type === ExamSessionType::Placement ? 0 : (int) $session->price,
            'purchased' => (int) ($purchases[ExamSessionPurchase::STATUS_PAID] ?? 0),
            'refund_pending' => (int) ($purchases[ExamSessionPurchase::STATUS_REFUND_PENDING] ?? 0),
            'refunded' => (int) ($purchases[ExamSessionPurchase::STATUS_REFUNDED] ?? 0),
            'via' => [
                'purchase' => (int) ($via[ExamAccess::VIA_PURCHASE] ?? 0),
                'subscription' => (int) ($via[ExamAccess::VIA_SUBSCRIPTION] ?? 0),
                // before paid exams access_via was not recorded: those attempts were free
                'free' => (int) ($via[ExamAccess::VIA_FREE] ?? 0) + (int) ($via[''] ?? 0),
            ],
        ];
    }

    private function stats(ExamSession $session): array
    {
        $attempts = ExamAttempt::query()->where('session_id', $session->id)
            ->selectRaw('status, count(*) as c, avg(score_percent) as avg_percent')
            ->groupBy('status')->get()->keyBy(fn ($r) => $r->status instanceof AttemptStatus ? $r->status->value : $r->status);
        $finished = collect([AttemptStatus::Submitted, AttemptStatus::AutoSubmitted, AttemptStatus::Graded])->sum(fn ($s) => (int) ($attempts[$s->value]->c ?? 0));
        $graded = $attempts[AttemptStatus::Graded->value] ?? null;

        return [
            'audience' => $this->audience->count($session),
            'variants' => ExamVariant::query()->where('session_id', $session->id)->count(),
            'in_progress' => (int) ($attempts[AttemptStatus::InProgress->value]->c ?? 0),
            'finished' => $finished,
            'average_percent' => $graded ? round((float) $graded->avg_percent, 1) : null,
        ];
    }

    private function row(ExamSession $s): array
    {
        return [
            'id' => $s->id,
            'title' => $s->title,
            'type' => $s->type?->value,
            'status' => $s->status?->value,
            'subject' => $s->subject ? ['code' => $s->subject->code, 'name' => $s->subject->name_uz] : null,
            'blueprint_name' => $s->blueprint?->name,
            'starts_at' => $s->starts_at?->toIso8601String(),
            'ends_at' => $s->ends_at?->toIso8601String(),
            'duration_min' => $s->effectiveDurationMin(),
            'variants_generated_at' => $s->variants_generated_at?->toIso8601String(),
            'variants_count' => $s->variants_count ?? null,
            'attempts_count' => $s->attempts_count ?? null,
            'finished_count' => $s->finished_count ?? null,
            'restricted' => $s->restricted_to_user_id !== null,
            'price' => $s->type === ExamSessionType::Placement ? 0 : (int) $s->price,
            'purchases_count' => $s->purchases_count ?? null,
            'via_purchase_count' => $s->via_purchase_count ?? null,
            'via_subscription_count' => $s->via_subscription_count ?? null,
        ];
    }

    private function resultRow(ExamAttempt $a, ?StudentWeeklyResult $weekly): array
    {
        $labels = [
            'in_progress' => 'Yechmoqda',
            'submitted' => 'Topshirdi',
            'auto_submitted' => 'Vaqt tugadi',
            'graded' => 'Baholandi',
            'invalidated' => 'Bekor qilingan',
        ];
        $status = $a->status?->value;
        if ($status === 'graded' && $a->submitted_at && $a->expires_at && $a->submitted_at->gte($a->expires_at)) {
            $label = 'Vaqt tugadi';
        }

        return [
            'id' => $a->id,
            'student' => trim(($a->user?->first_name ?? '').' '.($a->user?->last_name ?? '')) ?: '—',
            'status' => $status,
            'status_label' => $label ?? ($labels[$status] ?? $status),
            'score' => (float) $a->score,
            'max_score' => (float) $a->max_score,
            'score_percent' => (float) $a->score_percent,
            'correct_count' => $a->correct_count,
            'wrong_count' => $a->wrong_count,
            'skipped_count' => $a->skipped_count,
            'started_at' => $a->started_at?->toIso8601String(),
            'submitted_at' => $a->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $a->time_spent_seconds,
            'violations_count' => $a->violations_count,
            'rank' => $weekly?->rank,
            'delta_prev' => $weekly?->delta_prev === null ? null : (float) $weekly->delta_prev,
        ];
    }

    private function num(float|int|null $value): string
    {
        return $value === null ? '' : str_replace('.', ',', (string) round((float) $value, 2));
    }

    private function formProps(): array
    {
        return [
            'examPlans' => app(ExamAccess::class)->examPlanNames(),
            'subjects' => Subject::query()->active()->orderBy('sort_order')->get()->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name_uz])->values(),
            'blueprints' => ExamBlueprint::query()->active()
                ->whereIn('purpose', [ExamSessionType::Weekly->value, ExamSessionType::OpenRegional->value])
                ->orderBy('name')->get()
                ->map(fn (ExamBlueprint $b) => [
                    'id' => $b->id,
                    'subject_id' => $b->subject_id,
                    'name' => $b->name,
                    'purpose' => $b->purpose?->value,
                    'duration_min' => $b->duration_min,
                    'total_questions' => $b->total_questions,
                ])->values(),
        ];
    }
}
