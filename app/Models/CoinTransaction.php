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
        // Polymorphic link to the model that caused the transaction
        'reference_type', 'reference_id',
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
    
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias of reference(): coin_transactions only has reference_type/reference_id.
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo('transactionable', 'reference_type', 'reference_id');
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
