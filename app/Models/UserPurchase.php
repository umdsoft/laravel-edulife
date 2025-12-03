<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPurchase extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'item_id', 'price_paid', 'quantity',
        'remaining_uses', 'expires_at',
        'is_active', 'is_equipped',
    ];
    
    protected $casts = [
        'price_paid' => 'integer',
        'quantity' => 'integer',
        'remaining_uses' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_equipped' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function item(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'item_id');
    }
    
    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }
    
    public function isUsable(): bool
    {
        if (!$this->is_active) return false;
        if ($this->isExpired()) return false;
        if ($this->remaining_uses !== null && $this->remaining_uses <= 0) return false;
        
        return true;
    }
}
