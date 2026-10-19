<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoCodeRequest;
use App\Http\Requests\Admin\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of promo codes.
     */
    public function index(Request $request): Response
    {
        $query = PromoCode::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->get('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $promoCodes = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($code) => [
                'id' => $code->id,
                'code' => $code->code,
                'type' => $code->type,
                'value' => $code->value,
                'min_amount' => $code->min_purchase,
                'max_uses' => $code->usage_limit,
                'max_uses_per_user' => $code->per_user_limit,
                'used_count' => $code->usage_count,
                'starts_at' => $code->starts_at?->format('d.m.Y'),
                'expires_at' => $code->expires_at?->format('d.m.Y'),
                'is_active' => $code->is_active,
                'is_expired' => $code->expires_at && $code->expires_at->isPast(),
                'is_maxed_out' => $code->usage_limit && $code->usage_count >= $code->usage_limit,
            ]);

        return Inertia::render('Admin/PromoCodes/Index', [
            'promoCodes' => $promoCodes,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Store a newly created promo code.
     */
    public function store(StorePromoCodeRequest $request): RedirectResponse
    {
        $data = $this->toColumns($request->validated());
        $data['applies_to'] ??= 'all';
        $data['created_by'] = $request->user()->id;

        PromoCode::create($data);

        return back()->with('success', 'Promo kod yaratildi!');
    }

    /**
     * Update the specified promo code.
     */
    public function update(UpdatePromoCodeRequest $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($this->toColumns($request->validated()));

        return back()->with('success', 'Promo kod yangilandi!');
    }

    /**
     * Toggle promo code status.
     */
    public function toggleStatus(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update([
            'is_active' => !$promoCode->is_active,
        ]);

        return back()->with('success', 'Status o\'zgartirildi!');
    }

    /**
     * Map form field names to the promo_codes columns.
     */
    private function toColumns(array $data): array
    {
        $map = [
            'min_amount' => 'min_purchase',
            'max_uses' => 'usage_limit',
            'max_uses_per_user' => 'per_user_limit',
        ];

        foreach ($map as $field => $column) {
            if (array_key_exists($field, $data)) {
                $data[$column] = $data[$field];
                unset($data[$field]);
            }
        }

        if (array_key_exists('course_id', $data)) {
            $data['applies_to'] = $data['course_id'] ? 'specific_course' : 'all';
        }

        return $data;
    }

    /**
     * Remove the specified promo code.
     */
    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        return back()->with('success', 'Promo kod o\'chirildi!');
    }
}
