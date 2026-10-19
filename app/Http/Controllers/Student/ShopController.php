<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use App\Models\ShopItem;
use App\Models\UserPurchase;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShopController extends Controller
{
    /** Item types a student can wear; only one of each type is equipped. */
    private const EQUIPPABLE = ['avatar', 'badge', 'theme'];

    private const TYPE_LABELS = [
        'avatar' => 'Avatar',
        'badge' => 'Nishon',
        'theme' => 'Mavzu',
        'boost' => 'Kuchaytirgich',
        'hint' => 'Maslahat',
        'extra_life' => "Qo'shimcha hayot",
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);

        // Coin packages are bought with money (no payment gateway yet)
        $packages = CoinPackage::where('is_active', true)
            ->orderBy('is_popular', 'desc')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $owned = UserPurchase::where('user_id', $user->id)
            ->selectRaw('item_id, COUNT(*) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        // Items are bought with the coins the student earned
        $items = ShopItem::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->filter(fn (ShopItem $item) => $item->isAvailable())
            ->map(fn (ShopItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'type' => $item->type,
                'type_label' => self::TYPE_LABELS[$item->type] ?? $item->type,
                'image' => $item->image,
                // pictures exist for avatars and badges (public/images/shop)
                'image_url' => in_array($item->type, ['avatar', 'badge'], true) ? $item->image_url : null,
                'price' => (int) $item->price,
                'original_price' => $item->original_price ? (int) $item->original_price : null,
                'min_level' => (int) $item->min_level,
                'duration_hours' => $item->duration_hours,
                'is_featured' => (bool) $item->is_featured,
                'owned' => (int) ($owned[$item->id] ?? 0),
                'limit_reached' => $item->max_per_user && ($owned[$item->id] ?? 0) >= $item->max_per_user,
            ])
            ->values();

        return Inertia::render('Student/Shop/Index', [
            'packages' => $packages,
            'items' => $items,
            'balance' => (int) ($profile->coins ?? 0),
            'level' => (int) ($profile->level ?? 1),
            'onlinePaymentAvailable' => false,
        ]);
    }

    /**
     * Coin packages need an online payment provider, which is not connected.
     */
    public function purchase(Request $request, CoinPackage $item)
    {
        $message = "Coin paketlarini onlayn sotib olish hozircha ulanmagan. Coinlarni darslar, testlar va olimpiadalar orqali to'plang.";

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->with('info', $message);
    }

    /**
     * Buy a shop item with earned coins.
     */
    public function buyItem(ShopItem $item, CoinService $coins)
    {
        $user = Auth::user();
        if (! $user->studentProfile) {
            $user->studentProfile()->create([]);
            $user->load('studentProfile');
        }

        $result = $coins->purchaseItem($user, $item);

        if (! ($result['success'] ?? false)) {
            return back()->with('error', match ($result['message'] ?? '') {
                'Item not available' => 'Bu buyum hozir mavjud emas.',
                'Level too low' => "Bu buyum {$item->min_level}-darajadan ochiladi.",
                'Purchase limit reached' => 'Bu buyumni boshqa sotib ololmaysiz.',
                'Not enough coins' => "Coin yetarli emas: {$item->price} coin kerak.",
                default => "Xarid amalga oshmadi. Qayta urinib ko'ring.",
            });
        }

        return back()->with('success', "«{$item->name}» sotib olindi. U «Mening buyumlarim» bo'limida.");
    }

    public function myItems()
    {
        $user = Auth::user();

        $purchases = UserPurchase::with('item')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->filter(fn ($p) => $p->item)
            ->map(fn (UserPurchase $p) => [
                'id' => $p->id,
                'name' => $p->item->name,
                'description' => $p->item->description,
                'type' => $p->item->type,
                'type_label' => self::TYPE_LABELS[$p->item->type] ?? $p->item->type,
                'image' => $p->item->image,
                'image_url' => in_array($p->item->type, ['avatar', 'badge'], true) ? $p->item->image_url : null,
                'price_paid' => (int) $p->price_paid,
                'quantity' => (int) $p->quantity,
                'remaining_uses' => $p->remaining_uses,
                'expires_at' => $p->expires_at?->toIso8601String(),
                'is_expired' => $p->expires_at !== null && $p->expires_at->isPast(),
                'is_equipped' => (bool) $p->is_equipped,
                'equippable' => in_array($p->item->type, self::EQUIPPABLE, true),
                'purchased_at' => $p->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Student/Shop/MyItems', [
            'purchases' => $purchases,
            'balance' => (int) ($user->studentProfile?->coins ?? 0),
        ]);
    }

    /**
     * Wear (or take off) an avatar / badge / theme; one per type.
     */
    public function equip(UserPurchase $purchase)
    {
        abort_unless((string) $purchase->user_id === (string) Auth::id(), 403);
        $purchase->load('item');

        if (! $purchase->item || ! in_array($purchase->item->type, self::EQUIPPABLE, true)) {
            return back()->with('error', "Bu buyumni taqib bo'lmaydi.");
        }

        if ($purchase->is_equipped) {
            $purchase->update(['is_equipped' => false]);

            return back()->with('success', "«{$purchase->item->name}» olib qo'yildi.");
        }

        DB::transaction(function () use ($purchase) {
            UserPurchase::where('user_id', $purchase->user_id)
                ->whereHas('item', fn ($q) => $q->where('type', $purchase->item->type))
                ->update(['is_equipped' => false]);
            $purchase->update(['is_equipped' => true]);
        });

        return back()->with('success', "«{$purchase->item->name}» taqildi.");
    }
}
