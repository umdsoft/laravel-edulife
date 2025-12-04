<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CoinTransaction extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_after',
        'source', 'description', 'metadata',
        // Support both morphTo naming conventions
        'reference_type', 'reference_id',
        'transactionable_type', 'transactionable_id',
        'related_user_id', 'payment_id',
    ];
    
    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }
    
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
    
    // Support both naming conventions for morphTo
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
    
    // Scopes
    public function scopeEarnings($query)
    {
        return $query->where('type', 'earn');
    }
    
    public function scopeSpending($query)
    {
        return $query->where('type', 'spend');
    }
}
