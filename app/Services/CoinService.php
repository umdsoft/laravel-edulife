<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShopItem;
use App\Models\UserPurchase;
use App\Models\CoinTransaction;

class CoinService
{
    /**
     * Add coins to user balance
     */
    public function addCoins(User $user, int $amount, string $source, string $description, $transactionable = null): void
    {
        $profile = $user->studentProfile;
        $profile->increment('coins', $amount);
        $profile->increment('total_coins_earned', $amount);
        
        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $profile->fresh()->coins,
            'source' => $source,
            'description' => $description,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'transactionable_id' => $transactionable?->id,
        ]);
    }
    
    /**
     * Spend coins from user balance
     */
    public function spendCoins(User $user, int $amount, string $source, string $description, $transactionable = null): bool
    {
        $profile = $user->studentProfile;
        
        if ($profile->coins < $amount) {
            return false;
        }
        
        $profile->decrement('coins', $amount);
        
        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => -$amount,
            'balance_after' => $profile->fresh()->coins,
            'source' => $source,
            'description' => $description,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'transactionable_id' => $transactionable?->id,
        ]);
        
        return true;
    }
    
    /**
     * Purchase a shop item
     */
    public function purchaseItem(User $user, ShopItem $item, int $quantity = 1): array
    {
        $profile = $user->studentProfile;
        
        // Check if item is available
        if (!$item->isAvailable()) {
            return ['success' => false, 'message' => 'Item not available'];
        }
        
        // Check level requirement
        if ($profile->level < $item->min_level) {
            return ['success' => false, 'message' => 'Level too low'];
        }
        
        // Check purchase limit
        $userPurchaseCount = UserPurchase::where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->count();
            
        if ($userPurchaseCount >= $item->max_per_user) {
            return ['success' => false, 'message' => 'Purchase limit reached'];
        }
        
        // Calculate total cost
        $totalCost = $item->price * $quantity;
        
        // Check if user has enough coins
        if ($profile->coins < $totalCost) {
            return ['success' => false, 'message' => 'Not enough coins'];
        }
        
        // Deduct coins
        if (!$this->spendCoins($user, $totalCost, 'shop', "Purchased: {$item->name}", $item)) {
            return ['success' => false, 'message' => 'Transaction failed'];
        }
        
        // Create purchase record
        $purchase = UserPurchase::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'price_paid' => $item->price,
            'quantity' => $quantity,
            'remaining_uses' => $item->type === 'hint' || $item->type === 'extra_life' ? $quantity : null,
            'expires_at' => $item->duration_hours ? now()->addHours($item->duration_hours) : null,
        ]);
        
        // Update stock
        if ($item->stock !== null) {
            $item->decrement('stock', $quantity);
        }
        $item->increment('purchased_count', $quantity);
        
        return [
            'success' => true,
            'purchase' => $purchase,
        ];
    }
}
