<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopItem extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'name', 'description', 'image', 'type', 'category',
        'price', 'original_price', 'stock', 'purchased_count',
        'max_per_user', 'min_level', 'duration_hours', 'item_data',
        'is_active', 'is_featured', 'available_from', 'available_until', 'sort_order',
    ];
    
    protected $casts = [
        'price' => 'integer',
        'original_price' => 'integer',
        'stock' => 'integer',
        'purchased_count' => 'integer',
        'max_per_user' => 'integer',
        'min_level' => 'integer',
        'duration_hours' => 'integer',
        'item_data' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];
    
    protected $appends = ['is_on_sale', 'discount_percentage'];
    
    public function purchases(): HasMany
    {
        return $this->hasMany(UserPurchase::class, 'item_id');
    }
    
    public function getIsOnSaleAttribute(): bool
    {
        return $this->original_price !== null && $this->price < $this->original_price;
    }
    
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->is_on_sale) return null;
        return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
    }
    
    public function isAvailable(): bool
    {
        if (!$this->is_active) return false;
        if ($this->stock !== null && $this->stock <= 0) return false;
        
        $now = now();
        if ($this->available_from && $now->lt($this->available_from)) return false;
        if ($this->available_until && $now->gt($this->available_until)) return false;
        
        return true;
    }
}
