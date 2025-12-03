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
        'source', 'description',
        'transactionable_type', 'transactionable_id',
    ];
    
    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
