<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadQuestion;
use App\Models\OlympiadSeries;
use App\Models\OlympiadStage;
use App\Models\OlympiadType;
use App\Models\Region;
use App\Services\OlympiadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OlympiadController extends Controller
{
    protected OlympiadService $olympiadService;

    public function __construct(OlympiadService $olympiadService)
    {
        $this->olympiadService = $olympiadService;
    }

    /**
     * Display olympiad list
     */
    public function index(Request $request): Response
    {
        $query = Olympiad::with(['olympiadType', 'stage', 'creator'])
            ->withCount(['registrations', 'attempts']);

        // Filters
        if ($request->type_id) {
            $query->where('olympiad_type_id', $request->type_id);
        }
        if ($request->stage_id) {
            $query->where('stage_id', $request->stage_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $olympiads = $query->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Olympiad/Index', [
            'olympiads' => $olympiads->map(fn($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'slug' => $o->slug,
                'type' => $o->olympiadType->display_name,
                'type_color' => $o->olympiadType->color,
                'stage' => $o->stage?->display_name,
                'status' => $o->status,
                'status_color' => $o->status_color,
                'registration_fee' => $o->registration_fee,
                'olympiad_start_at' => $o->olympiad_start_at?->format('d.m.Y H:i'),
                'registrations_count' => $o->registrations_count,
                'attempts_count' => $o->attempts_count,
                'created_by' => $o->creator?->name,
                'created_at' => $o->created_at->format('d.m.Y'),
            ]),
            'pagination' => [
                'current_page' => $olympiads->currentPage(),
                'last_page' => $olympiads->lastPage(),
                'total' => $olympiads->total(),
            ],
            'filters' => [
                'types' => OlympiadType::active()->get(['id', 'display_name', 'display_name_uz', 'color']),
                'stages' => OlympiadStage::orderBy('order_level')->get(['id', 'display_name', 'display_name_uz']),
                'statuses' => Olympiad::STATUSES,
            ],
            'currentFilters' => [
                'type_id' => $request->type_id,
                'stage_id' => $request->stage_id,
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show create form
     */
    public function create(): Response
    {
        OlympiadType::ensureDefaults();

        return Inertia::render('Admin/Olympiad/Create', [
            'types' => OlympiadType::active()->with('direction')->get(),
            'stages' => OlympiadStage::orderBy('order_level')->get(),
            'series' => OlympiadSeries::active()->get(),
            'regions' => Region::active()->get(),
        ]);
    }

    /**
     * Store new olympiad
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'rules' => 'nullable|string',
            'olympiad_type_id' => 'required|uuid|exists:olympiad_types,id',
            'stage_id' => 'nullable|uuid|exists:olympiad_stages,id',
            'series_id' => 'nullable|uuid|exists:olympiad_series,id',
            'region_id' => 'nullable|uuid|exists:regions,id',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_start_at' => 'nullable|date',
            'registration_end_at' => 'nullable|date|after:registration_start_at',
            'olympiad_start_at' => 'required|date',
            'olympiad_end_at' => 'required|date|after:olympiad_start_at',
            'max_participants' => 'nullable|integer|min:1',
            'demo_config' => 'nullable|array',
            'anti_cheat_config' => 'nullable|array',
            'reward_config' => 'nullable|array',
            'create_default_sections' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = Olympiad::STATUS_DRAFT;

        $olympiad = $this->olympiadService->create($validated);

        return redirect()->route('admin.olympiads.edit', $olympiad->id)
            ->with('success', 'Olimpiada muvaffaqiyatli yaratildi');
    }

    /**
     * Show olympiad details
     */
    public function show(string $id): Response
    {
        $olympiad = Olympiad::with([
            'olympiadType',
            'stage',
            'series',
            'sections' => fn($q) => $q->ordered(),
            'creator',
        ])->findOrFail($id);

        $statistics = $this->olympiadService->getStatistics($olympiad);

        return Inertia::render('Admin/Olympiad/Show', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'description' => $olympiad->description,
                'type' => $olympiad->olympiadType,
                'stage' => $olympiad->stage,
                'series' => $olympiad->series,
                'status' => $olympiad->status,
                'sections' => $olympiad->sections,
                'schedule' => [
                    'registration_start_at' => $olympiad->registration_start_at?->format('d.m.Y H:i'),
                    'registration_end_at' => $olympiad->registration_end_at?->format('d.m.Y H:i'),
                    'olympiad_start_at' => $olympiad->olympiad_start_at?->format('d.m.Y H:i'),
                    'olympiad_end_at' => $olympiad->olympiad_end_at?->format('d.m.Y H:i'),
                ],
                'pricing' => [
                    'registration_fee' => $olympiad->registration_fee,
                    'demo_price' => $olympiad->demo_config['price'] ?? 0,
                ],
                'created_by' => $olympiad->creator?->full_name,
            ],
            'statistics' => $statistics,
            'actions' => $this->olympiadService->managementActions($olympiad),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(string $id): Response
    {
        OlympiadType::ensureDefaults();

        $olympiad = Olympiad::with(['sections' => fn($q) => $q->ordered()->withCount('questions')])
            ->findOrFail($id);

        // datetime-local inputs want "Y-m-d\TH:i" in the app timezone
        $local = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('Y-m-d\TH:i') ?? '';

        return Inertia::render('Admin/Olympiad/Edit', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'status' => $olympiad->status,
                'editable' => ! in_array($olympiad->status, [Olympiad::STATUS_LIVE, Olympiad::STATUS_COMPLETED], true),
            ],
            'form' => [
                'title' => $olympiad->title,
                'description' => $olympiad->description ?? '',
                'short_description' => $olympiad->short_description ?? '',
                'rules' => $olympiad->rules ?? '',
                'olympiad_type_id' => $olympiad->olympiad_type_id,
                'stage_id' => $olympiad->stage_id,
                'series_id' => $olympiad->series_id,
                'region_id' => $olympiad->region_id,
                'registration_fee' => (float) $olympiad->entry_fee,
                'registration_start_at' => $local($olympiad->registration_start_at),
                'registration_end_at' => $local($olympiad->registration_end_at),
                'olympiad_start_at' => $local($olympiad->olympiad_start_at),
                'olympiad_end_at' => $local($olympiad->olympiad_end_at),
                'max_participants' => $olympiad->max_participants,
                'demo_config' => array_merge(['enabled' => (bool) $olympiad->demo_enabled, 'price' => 0, 'max_attempts' => 3], (array) $olympiad->demo_config),
                'anti_cheat_config' => array_merge(['fullscreen_required' => true, 'device_lock' => true, 'max_tab_switches' => 3, 'max_warnings' => 5], (array) $olympiad->anti_cheat_config),
                'reward_config' => array_merge(['coins_per_correct' => 1, 'top_10_coins' => 100], (array) $olympiad->reward_config),
                'visibility' => $olympiad->visibility ?? 'public',
            ],
            'sections' => $olympiad->sections->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'section_type' => $s->section_type,
                'duration_minutes' => $s->duration_minutes,
                'max_points' => $s->max_points,
                'weight_percent' => (float) $s->weight_percent,
                'questions_count' => $s->questions_count,
            ]),
            'types' => OlympiadType::active()->with('direction')->get(),
            'stages' => OlympiadStage::orderBy('order_level')->get(),
            'series' => OlympiadSeries::active()->get(),
            'regions' => Region::active()->get(),
        ]);
    }

    /**
     * Update olympiad
     */
    public function update(Request $request, string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'rules' => 'nullable|string',
            'olympiad_type_id' => 'required|uuid|exists:olympiad_types,id',
            'stage_id' => 'nullable|uuid|exists:olympiad_stages,id',
            'series_id' => 'nullable|uuid|exists:olympiad_series,id',
            'region_id' => 'nullable|uuid|exists:regions,id',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_start_at' => 'nullable|date',
            'registration_end_at' => 'nullable|date|after:registration_start_at',
            'olympiad_start_at' => 'required|date',
            'olympiad_end_at' => 'required|date|after:olympiad_start_at',
            'max_participants' => 'nullable|integer|min:1',
            'demo_config' => 'nullable|array',
            'anti_cheat_config' => 'nullable|array',
            'reward_config' => 'nullable|array',
            'visibility' => 'nullable|in:public,private,invited',
        ]);

        try {
            $this->olympiadService->update($olympiad, $validated);
            return redirect()->route('admin.olympiads.show', $id)
                ->with('success', 'Olimpiada muvaffaqiyatli yangilandi');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Amalni bajarib bo'lmadi. Iltimos, qayta urinib ko'ring.");
        }
    }

    /**
     * Update olympiad status
     */
    public function updateStatus(Request $request, string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        $request->validate([
            'status' => 'required|in:' . implode(',', Olympiad::STATUSES),
        ]);

        try {
            $this->olympiadService->updateStatus($olympiad, $request->status);

            return back()->with('success', match ($request->status) {
                Olympiad::STATUS_DRAFT => 'Olimpiada qoralamaga qaytarildi',
                Olympiad::STATUS_CANCELLED => "Olimpiada bekor qilindi, to'lovlar qaytarildi",
                default => 'Status muvaffaqiyatli o\'zgartirildi',
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Amalni bajarib bo'lmadi. Iltimos, qayta urinib ko'ring.");
        }
    }

    /**
     * Duplicate olympiad
     */
    public function duplicate(Request $request, string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'olympiad_start_at' => 'required|date',
            'olympiad_end_at' => 'required|date|after:olympiad_start_at',
        ]);

        $newOlympiad = $this->olympiadService->duplicate($olympiad, [
            'title' => $request->title,
            'olympiad_start_at' => $request->olympiad_start_at,
            'olympiad_end_at' => $request->olympiad_end_at,
            'registration_start_at' => $request->registration_start_at,
            'registration_end_at' => $request->registration_end_at,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.olympiads.edit', $newOlympiad->id)
            ->with('success', 'Olimpiada muvaffaqiyatli nusxalandi');
    }

    /**
     * Delete olympiad
     */
    public function destroy(string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        // only an olympiad nobody signed up for / sat; otherwise it is cancelled (refunds), not erased
        if (! $this->olympiadService->managementActions($olympiad)['can_delete']) {
            return back()->with('error', "Ro'yxatdan o'tganlar yoki urinishlar bor — o'chirib bo'lmaydi. Olimpiadani «Bekor qilish» mumkin.");
        }

        $olympiad->sections()->delete();
        $olympiad->delete();

        return redirect()->route('admin.olympiads.index')
            ->with('success', 'Olimpiada o\'chirildi');
    }

    /**
     * Fill a section with approved single-choice questions of the olympiad's
     * subject from the question bank (one point each). Without questions the
     * exam has nothing to show.
     */
    public function assignQuestions(Request $request, string $id, string $sectionId)
    {
        $olympiad = Olympiad::with('olympiadType')->findOrFail($id);
        $section = $olympiad->sections()->findOrFail($sectionId);

        if (in_array($olympiad->status, [Olympiad::STATUS_LIVE, Olympiad::STATUS_COMPLETED, Olympiad::STATUS_GRADING], true)) {
            return back()->with('error', "Boshlangan olimpiadaning savollarini o'zgartirib bo'lmaydi.");
        }

        $data = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:100']]);

        $subjectId = \App\Models\Exam\Subject::where('code', $olympiad->olympiadType?->name)->value('id');
        $taken = OlympiadQuestion::where('olympiad_id', $olympiad->id)->pluck('question_id');

        $questions = \App\Models\QuestionBank::query()
            ->where('question_type', 'single_choice')
            ->where('review_status', 'approved')
            ->whereNotNull('options')
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->whereNotIn('id', $taken)
            ->inRandomOrder()
            ->limit($data['count'])
            ->get(['id']);

        if ($questions->isEmpty()) {
            return back()->with('warning', "Savollar bankida bu fan bo'yicha tasdiqlangan test savollari qolmadi.");
        }

        DB::transaction(function () use ($olympiad, $section, $questions) {
            $order = (int) OlympiadQuestion::where('section_id', $section->id)->max('order_number');
            foreach ($questions as $question) {
                OlympiadQuestion::create([
                    'olympiad_id' => $olympiad->id,
                    'section_id' => $section->id,
                    'question_id' => $question->id,
                    'order_number' => ++$order,
                    'points' => 1,
                ]);
            }

            // the section is worth as many points as it has questions
            $section->update(['max_points' => OlympiadQuestion::where('section_id', $section->id)->sum('points')]);
            $this->olympiadService->syncTotals($olympiad);
        });

        $added = $questions->count();
        $note = $added < $data['count'] ? " (bankda shuncha savol bor edi)" : '';

        return back()->with('success', "{$added} ta savol qo'shildi{$note}.");
    }

    public function clearQuestions(string $id, string $sectionId)
    {
        $olympiad = Olympiad::findOrFail($id);
        $section = $olympiad->sections()->findOrFail($sectionId);

        if (in_array($olympiad->status, [Olympiad::STATUS_LIVE, Olympiad::STATUS_COMPLETED, Olympiad::STATUS_GRADING], true)) {
            return back()->with('error', "Boshlangan olimpiadaning savollarini o'zgartirib bo'lmaydi.");
        }

        OlympiadQuestion::where('section_id', $section->id)->delete();
        $section->update(['max_points' => 0]);

        return back()->with('success', "Bo'lim savollari olib tashlandi.");
    }

    /**
     * Get registrations for olympiad
     */
    public function registrations(Request $request, string $id): Response
    {
        $olympiad = Olympiad::findOrFail($id);
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $registrations = $olympiad->registrations()
            ->with(['user:id,first_name,last_name,email,phone', 'school:id,name'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    foreach (preg_split('/\s+/', $search) as $word) {
                        $term = '%'.addcslashes($word, '%_\\').'%';
                        $u->where(fn ($w) => $w->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('phone', 'like', $term));
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->user?->full_name,
                // admins see contacts of participants
                'user_email' => $r->user?->email,
                'user_phone' => $r->user?->phone,
                'school' => $r->school?->name,
                'status' => $r->status,
                'final_price' => (float) $r->final_price,
                'demo_purchased' => (bool) $r->demo_purchased,
                'registered_at' => ($r->registered_at ?? $r->created_at)?->format('d.m.Y H:i'),
                'confirmed_at' => $r->confirmed_at?->format('d.m.Y H:i'),
            ]);

        $counts = $olympiad->registrations()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n);

        return Inertia::render('Admin/Olympiad/Registrations', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'status' => $olympiad->status,
                'max_participants' => $olympiad->max_participants,
            ],
            'registrations' => $registrations,
            'counts' => $counts,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }
}
