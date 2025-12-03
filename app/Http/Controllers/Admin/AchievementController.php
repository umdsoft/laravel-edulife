<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAchievementRequest;
use App\Http\Requests\Admin\UpdateAchievementRequest;
use App\Models\Achievement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    /**
     * Display a listing of achievements.
     */
    public function index(Request $request): Response
    {
        $query = Achievement::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Filter by rarity
        if ($rarity = $request->get('rarity')) {
            $query->where('rarity', $rarity);
        }

        $achievements = $query->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($achievement) => [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'title' => $achievement->title,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'category' => $achievement->category,
                'rarity' => $achievement->rarity,
                'xp_reward' => $achievement->xp_reward,
                'coin_reward' => $achievement->coin_reward,
                'is_hidden' => $achievement->is_hidden,
                'is_active' => $achievement->is_active,
            ]);

        return Inertia::render('Admin/Achievements/Index', [
            'achievements' => $achievements,
            'filters' => $request->only(['search', 'category', 'rarity']),
        ]);
    }

    /**
     * Store a newly created achievement.
     */
    public function store(StoreAchievementRequest $request): RedirectResponse
    {
        Achievement::create($request->validated());

        return back()->with('success', 'Yutuq yaratildi!');
    }

    /**
     * Update the specified achievement.
     */
    public function update(UpdateAchievementRequest $request, Achievement $achievement): RedirectResponse
    {
        $achievement->update($request->validated());

        return back()->with('success', 'Yutuq yangilandi!');
    }

    /**
     * Toggle achievement status.
     */
    public function toggleStatus(Achievement $achievement): RedirectResponse
    {
        $achievement->update([
            'is_active' => !$achievement->is_active,
        ]);

        return back()->with('success', 'Status o\'zgartirildi!');
    }

    /**
     * Remove the specified achievement.
     */
    public function destroy(Achievement $achievement): RedirectResponse
    {
        $achievement->delete();

        return back()->with('success', 'Yutuq o\'chirildi!');
    }
}
