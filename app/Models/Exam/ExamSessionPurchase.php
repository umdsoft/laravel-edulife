<?php

namespace App\Models\Exam;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid entry to one exam session (payment product "exam_session"), one per student and session.
 * Granted by PaymentFulfillmentService; checked by App\Services\Exam\ExamAccess.
 */
class ExamSessionPurchase extends Model
{
    use HasUuids;

    public const STATUS_PAID = 'paid';
    public const STATUS_REFUND_PENDING = 'refund_pending';
    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'exam_session_purchases';

    protected $fillable = [
        'session_id', 'user_id', 'payment_order_id', 'amount', 'provider', 'status',
        'paid_at', 'refund_requested_at', 'refunded_at', 'refunded_by', 'refund_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'refund_requested_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
