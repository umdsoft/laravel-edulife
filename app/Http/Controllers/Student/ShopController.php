<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use App\Models\UserPurchase;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function __construct(
        protected CoinService $coinService
    ) {}
    
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        
        $query = ShopItem::where('is_active', true);
        
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->category) {
            $query->where('category', $request->category);
        }
        
        $items = $query->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Student/Shop/Index', [
            'items' => $items,
            'balance' => $profile->coins,
            'level' => $profile->level,
        ]);
    }
    
    public function purchase(Request $request, ShopItem $item)
    {
        $user = Auth::user();
        
        $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);
        
        $quantity = $request->quantity ?? 1;
        
        $result = $this->coinService->purchaseItem($user, $item, $quantity);
        
        return response()->json($result);
    }
    
    public function myItems()
    {
        $user = Auth::user();
        
        $purchases = UserPurchase::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('item')
            ->orderByDesc('created_at')
            ->get();
        
        return Inertia::render('Student/Shop/MyItems', [
            'purchases' => $purchases,
        ]);
    }
    
    public function equip(UserPurchase $purchase)
    {
        $user = Auth::user();
        
        if ($purchase->user_id !== $user->id) {
            abort(403);
        }
        
        // Unequip all items of the same type
        UserPurchase::where('user_id', $user->id)
            ->whereHas('item', function ($q) use ($purchase) {
                $q->where('type', $purchase->item->type);
            })
            ->update(['is_equipped' => false]);
        
        // Equip this item
        $purchase->update(['is_equipped' => true]);
        
        return response()->json(['success' => true]);
    }
}
