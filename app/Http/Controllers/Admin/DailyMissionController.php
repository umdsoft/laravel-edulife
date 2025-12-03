<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDailyMissionRequest;
use App\Models\DailyMission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyMissionController extends Controller
{
    /**
     * Display a listing of daily missions.
     */
    public function index(): Response
    {
        $missions = DailyMission::orderBy('sort_order')->get()->map(fn ($mission) => [
            'id' => $mission->id,
            'code' => $mission->code,
            'title' => $mission->title,
            'description' => $mission->description,
            'icon' => $mission->icon,
            'type' => $mission->type,
            'target_count' => $mission->target_count,
            'xp_reward' => $mission->xp_reward,
            'coin_reward' => $mission->coin_reward,
            'difficulty' => $mission->difficulty,
            'is_active' => $mission->is_active,
            'sort_order' => $mission->sort_order,
        ]);

        return Inertia::render('Admin/DailyMissions/Index', [
            'missions' => $missions,
        ]);
    }

    /**
     * Store a newly created daily mission.
     */
    public function store(StoreDailyMissionRequest $request): RedirectResponse
    {
        DailyMission::create($request->validated());

        return back()->with('success', 'Kunlik vazifa yaratildi!');
    }

    /**
     * Update the specified daily mission.
     */
    public function update(StoreDailyMissionRequest $request, DailyMission $dailyMission): RedirectResponse
    {
        $dailyMission->update($request->validated());

        return back()->with('success', 'Kunlik vazifa yangilandi!');
    }

    /**
     * Toggle daily mission status.
     */
    public function toggleStatus(DailyMission $dailyMission): RedirectResponse
    {
        $dailyMission->update([
            'is_active' => !$dailyMission->is_active,
        ]);

        return back()->with('success', 'Status o\'zgartirildi!');
    }

    /**
     * Remove the specified daily mission.
     */
    public function destroy(DailyMission $dailyMission): RedirectResponse
    {
        $dailyMission->delete();

        return back()->with('success', 'Kunlik vazifa o\'chirildi!');
    }
}
