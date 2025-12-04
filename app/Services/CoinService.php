<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShopItem;
use App\Models\UserPurchase;
use App\Models\CoinTransaction;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for managing user coin balance and transactions.
 * 
 * This service handles all coin-related operations including:
 * - Adding coins (rewards, achievements, etc.)
 * - Spending coins (purchases, entry fees, etc.)
 * - Shop item purchases
 * 
 * @package App\Services
 * @author EDULIFE Team
 */
class CoinService
{
    /**
     * Add coins to user's balance.
     * 
     * Increments the user's coin balance and records a transaction.
     * Also updates the total_coins_earned stat for leaderboards.
     * 
     * @param User $user The user to add coins to
     * @param int $amount Number of coins to add (must be positive)
     * @param string $source Source identifier (e.g., 'lesson_complete', 'achievement', 'test_pass')
     * @param string $description Human-readable description of the reward
     * @param Model|null $transactionable Optional related model (e.g., Lesson, Achievement)
     * 
     * @return void
     * 
     * @example
     * $coinService->addCoins($user, 100, 'lesson_complete', 'Completed: Introduction to PHP', $lesson);
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
     * Spend coins from user's balance.
     * 
     * Checks if user has sufficient balance, then decrements
     * and records the transaction.
     * 
     * @param User $user The user spending coins
     * @param int $amount Number of coins to spend (must be positive)
     * @param string $source Source identifier (e.g., 'shop', 'tournament', 'battle')
     * @param string $description Human-readable description of the spend
     * @param Model|null $transactionable Optional related model (e.g., ShopItem, Tournament)
     * 
     * @return bool True if successful, false if insufficient balance
     * 
     * @example
     * if ($coinService->spendCoins($user, 50, 'tournament', 'Entry fee: Weekly Challenge', $tournament)) {
     *     // Proceed with tournament registration
     * }
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
     * Purchase a shop item for the user.
     * 
     * Validates all purchase requirements:
     * - Item availability (active, in stock)
     * - User level requirement
     * - Purchase limit per user
     * - Sufficient coin balance
     * 
     * On success, deducts coins, creates purchase record,
     * and updates item stock/purchase count.
     * 
     * @param User $user The user making the purchase
     * @param ShopItem $item The item to purchase
     * @param int $quantity Number of items to purchase (default: 1)
     * 
     * @return array{success: bool, message?: string, purchase?: UserPurchase}
     *         Returns success status and either error message or purchase record
     * 
     * @example
     * $result = $coinService->purchaseItem($user, $avatarItem);
     * if ($result['success']) {
     *     $purchase = $result['purchase'];
     *     // Update user's equipped avatar
     * } else {
     *     return back()->with('error', $result['message']);
     * }
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
