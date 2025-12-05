<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadSeries;
use App\Models\OlympiadStage;
use App\Models\OlympiadType;
use App\Models\Region;
use App\Services\OlympiadService;
use Illuminate\Http\Request;
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
                'types' => OlympiadType::active()->get(['id', 'display_name', 'color']),
                'stages' => OlympiadStage::orderBy('order_level')->get(['id', 'display_name']),
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
                'created_by' => $olympiad->creator?->name,
            ],
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(string $id): Response
    {
        $olympiad = Olympiad::with(['sections' => fn($q) => $q->ordered()])
            ->findOrFail($id);

        return Inertia::render('Admin/Olympiad/Edit', [
            'olympiad' => $olympiad,
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
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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
            return back()->with('success', 'Status muvaffaqiyatli o\'zgartirildi');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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

        if ($olympiad->registrations()->exists()) {
            return back()->with('error', 'Ro\'yxatdan o\'tganlar bor, o\'chirish mumkin emas');
        }

        $olympiad->sections()->delete();
        $olympiad->delete();

        return redirect()->route('admin.olympiads.index')
            ->with('success', 'Olimpiada o\'chirildi');
    }

    /**
     * Get registrations for olympiad
     */
    public function registrations(string $id): Response
    {
        $olympiad = Olympiad::findOrFail($id);

        $registrations = $olympiad->registrations()
            ->with(['user', 'school'])
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Admin/Olympiad/Registrations', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
            ],
            'registrations' => $registrations->map(fn($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name,
                'user_email' => $r->user->email,
                'school' => $r->school?->name,
                'status' => $r->status,
                'final_price' => $r->final_price,
                'demo_purchased' => $r->demo_purchased,
                'registered_at' => $r->registered_at?->format('d.m.Y H:i'),
                'confirmed_at' => $r->confirmed_at?->format('d.m.Y H:i'),
            ]),
            'pagination' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'total' => $registrations->total(),
            ],
        ]);
    }
}
