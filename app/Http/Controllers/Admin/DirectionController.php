<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDirectionRequest;
use App\Http\Requests\Admin\UpdateDirectionRequest;
use App\Models\Direction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectionController extends Controller
{
    /**
     * Display a listing of directions.
     */
    public function index(): Response
    {
        $directions = Direction::withCount('courses')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($direction) => [
                'id' => $direction->id,
                'name_uz' => $direction->name_uz,
                'name_ru' => $direction->name_ru,
                'name_en' => $direction->name_en,
                'slug' => $direction->slug,
                'description' => $direction->description,
                'icon' => $direction->icon,
                'color' => $direction->color,
                'is_active' => $direction->is_active,
                'sort_order' => $direction->sort_order,
                'courses_count' => $direction->courses_count,
            ]);

        return Inertia::render('Admin/Directions/Index', [
            'directions' => $directions,
        ]);
    }

    /**
     * Store a newly created direction.
     */
    public function store(StoreDirectionRequest $request): RedirectResponse
    {
        $maxSort = Direction::max('sort_order') ?? 0;

        Direction::create([
            ...$request->validated(),
            'is_active' => true,
            'sort_order' => $maxSort + 1,
            'courses_count' => 0,
        ]);

        return back()->with('success', 'Yo\'nalish muvaffaqiyatli yaratildi!');
    }

    /**
     * Update the specified direction.
     */
    public function update(UpdateDirectionRequest $request, Direction $direction): RedirectResponse
    {
        $direction->update($request->validated());

        return back()->with('success', 'Yo\'nalish muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified direction.
     */
    public function destroy(Direction $direction): RedirectResponse
    {
        if ($direction->courses()->count() > 0) {
            return back()->withErrors(['error' => 'Bu yo\'nalishda kurslar mavjud. Avval kurslarni o\'chiring.']);
        }

        $direction->delete();

        return back()->with('success', 'Yo\'nalish muvaffaqiyatli o\'chirildi!');
    }

    /**
     * Toggle direction status.
     */
    public function toggleStatus(Direction $direction): RedirectResponse
    {
        $direction->update(['is_active' => !$direction->is_active]);

        return back()->with('success', 'Status muvaffaqiyatli o\'zgartirildi!');
    }

    /**
     * Reorder directions.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:directions,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            Direction::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Tartib muvaffaqiyatli o\'zgartirildi!');
    }
}
