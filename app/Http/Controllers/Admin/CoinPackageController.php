<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoinPackageRequest;
use App\Models\CoinPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoinPackageController extends Controller
{
    /**
     * Display a listing of coin packages.
     */
    public function index(): Response
    {
        $packages = CoinPackage::orderBy('sort_order')->get()->map(fn ($package) => [
            'id' => $package->id,
            'name' => $package->name,
            'coins' => $package->coins,
            'bonus_coins' => $package->bonus_coins,
            'total_coins' => $package->total_coins,
            'price' => $package->price,
            'original_price' => $package->original_price,
            'badge' => $package->badge,
            'is_featured' => (bool) $package->is_popular,
            'is_active' => $package->is_active,
            'sort_order' => $package->sort_order,
        ]);

        return Inertia::render('Admin/CoinPackages/Index', [
            'packages' => $packages,
        ]);
    }

    /**
     * Store a newly created coin package.
     */
    public function store(StoreCoinPackageRequest $request): RedirectResponse
    {
        CoinPackage::create($this->attributes($request));

        return back()->with('success', 'COIN paketi yaratildi!');
    }

    /**
     * Update the specified coin package.
     */
    public function update(StoreCoinPackageRequest $request, CoinPackage $coinPackage): RedirectResponse
    {
        $coinPackage->update($this->attributes($request));

        return back()->with('success', 'COIN paketi yangilandi!');
    }

    /**
     * Toggle coin package status.
     */
    public function toggleStatus(CoinPackage $coinPackage): RedirectResponse
    {
        $coinPackage->update([
            'is_active' => !$coinPackage->is_active,
        ]);

        return back()->with('success', 'Status o\'zgartirildi!');
    }

    /**
     * Reorder coin packages.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'packages' => 'required|array',
            'packages.*.id' => 'required|exists:coin_packages,id',
            'packages.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->packages as $packageData) {
            CoinPackage::where('id', $packageData['id'])->update([
                'sort_order' => $packageData['sort_order'],
            ]);
        }

        return back()->with('success', 'Tartib o\'zgartirildi!');
    }

    /**
     * Remove the specified coin package.
     */
    public function destroy(CoinPackage $coinPackage): RedirectResponse
    {
        $coinPackage->delete();

        return back()->with('success', 'COIN paketi o\'chirildi!');
    }

    /**
     * The form's "Tavsiya etilgan" flag is the is_popular column.
     */
    private function attributes(StoreCoinPackageRequest $request): array
    {
        $data = $request->validated();
        $data['is_popular'] = (bool) ($data['is_featured'] ?? false);
        unset($data['is_featured']);
        $data['total_coins'] = $data['coins'] + ($data['bonus_coins'] ?? 0);

        return $data;
    }
}
