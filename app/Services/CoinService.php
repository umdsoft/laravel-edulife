<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShopItem;
use App\Models\UserPurchase;
use App\Models\CoinTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
     * @return int The coins actually added (the monthly limit can lower it)
     * 
     * @example
     * $coinService->addCoins($user, 100, 'lesson_complete', 'Completed: Introduction to PHP', $lesson);
     */
    public function addCoins(User $user, int $amount, string $source, string $description, $transactionable = null): int
    {
        $amount = $this->withinMonthlyLimit($user, $amount, $source);
        if ($amount <= 0) {
            return 0;
        }

        $profile = $user->ensureStudentProfile();
        $profile->increment('coins', $amount);
        // the leaderboard's "earned" stat: coins given back are not earnings
        if (! str_ends_with($source, 'refund')) {
            $profile->increment('total_coins_earned', $amount);
        }
        
        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $profile->fresh()->coins,
            'source' => $source,
            'description' => $description,
            'reference_type' => $transactionable ? $transactionable->getMorphClass() : null,
            'reference_id' => $transactionable?->getKey(),
        ]);

        return $amount;
    }

    /** Coins given back or bought — never counted against the monthly earning limit. */
    private const LIMIT_EXEMPT_SOURCES = ['purchase', 'coin_package', 'admin', 'admin_grant', 'legacy_transfer'];

    /**
     * The admin's "Oylik coin limiti" for the student's plan (0 = no limit):
     * rewards stop once the coins earned this calendar month reach it.
     * Refunds and bought coins are not earnings and always go through.
     */
    private function withinMonthlyLimit(User $user, int $amount, string $source): int
    {
        if ($amount <= 0 || str_ends_with($source, 'refund') || in_array($source, self::LIMIT_EXEMPT_SOURCES, true)) {
            return max(0, $amount);
        }

        $limit = app(RewardSettings::class)->monthlyCoinLimit($user);
        if ($limit <= 0) {
            return $amount;
        }

        $earned = (int) CoinTransaction::where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('source', 'not like', '%refund')
            ->whereNotIn('source', self::LIMIT_EXEMPT_SOURCES)
            ->sum('amount');

        return max(0, min($amount, $limit - $earned));
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
        $profile = $user->ensureStudentProfile();

        // Conditional decrement: never lets the balance go negative under concurrency
        $updated = $profile->newQuery()
            ->whereKey($profile->id)
            ->where('coins', '>=', $amount)
            ->decrement('coins', $amount);

        if ($updated === 0) {
            return false;
        }

        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => -$amount,
            'balance_after' => $profile->fresh()->coins,
            'source' => $source,
            'description' => $description,
            'reference_type' => $transactionable ? $transactionable->getMorphClass() : null,
            'reference_id' => $transactionable?->getKey(),
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
        return DB::transaction(function () use ($user, $item, $quantity) {
            $item = ShopItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            return $this->performPurchase($user, $item, $quantity);
        });
    }

    private function performPurchase(User $user, ShopItem $item, int $quantity): array
    {
        // read the current balance, not a relation cached earlier in the request
        $profile = $user->studentProfile()->first();

        if ($quantity < 1) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        // Check if item is available
        if (!$item->isAvailable() || ($item->stock !== null && $item->stock < $quantity)) {
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
        if (!$this->spendCoins($user, $totalCost, 'shop', "Sotib olindi: {$item->name}", $item)) {
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
