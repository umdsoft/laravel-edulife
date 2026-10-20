<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A provider transaction for a PaymentOrder.
 * State codes follow Payme (https://developer.help.paycom.uz/metody-merchant-api/tipy-dannykh):
 *   1 created (Click: prepared), 2 performed (Click: completed), -1 cancelled, -2 cancelled after perform.
 */
class PaymentTransaction extends Model
{
    public const STATE_CREATED = 1;
    public const STATE_PERFORMED = 2;
    public const STATE_CANCELLED = -1;
    public const STATE_CANCELLED_AFTER_PERFORM = -2;

    /** Payme cancel reasons */
    public const REASON_RECEIVERS_NOT_FOUND = 1;
    public const REASON_DEBIT_FAILED = 2;
    public const REASON_EXECUTION_FAILED = 3;
    public const REASON_TIMEOUT = 4;
    public const REASON_REFUND = 5;
    public const REASON_UNKNOWN = 10;

    protected $fillable = [
        'payment_order_id', 'provider', 'provider_transaction_id', 'state', 'amount_tiyin',
        'provider_time', 'create_time', 'perform_time', 'cancel_time', 'reason',
        'click_paydoc_id', 'account', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'integer',
            'amount_tiyin' => 'integer',
            'provider_time' => 'integer',
            'create_time' => 'integer',
            'perform_time' => 'integer',
            'cancel_time' => 'integer',
            'reason' => 'integer',
            'account' => 'array',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    /** Milliseconds since the epoch (Payme Timestamp type). */
    public static function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
