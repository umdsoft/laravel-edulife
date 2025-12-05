<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadPaymentTransaction extends Model
{
    use HasFactory, HasUuids;

    // Payment type constants
    public const TYPE_OLYMPIAD_REGISTRATION = 'olympiad_registration';
    public const TYPE_DEMO_PURCHASE = 'demo_purchase';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'registration_id',
        'payment_type',
        'payment_method',
        'amount',
        'currency',
        'coins_amount',
        'provider_transaction_id',
        'provider_response',
        'status',
        'status_message',
        'completed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'coins_amount' => 'integer',
            'provider_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(OlympiadRegistration::class, 'registration_id');
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('payment_type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, '', ' ') . ' ' . $this->currency;
    }

    // ==================== METHODS ====================

    /**
     * Mark as completed
     */
    public function complete(string $providerTransactionId = null, array $providerResponse = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        
        if ($providerTransactionId) {
            $this->provider_transaction_id = $providerTransactionId;
        }
        
        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }
        
        $this->save();

        // Confirm registration if this is a registration payment
        if ($this->payment_type === self::TYPE_OLYMPIAD_REGISTRATION && $this->registration) {
            $this->registration->confirm();
        }
    }

    /**
     * Mark as failed
     */
    public function fail(string $message = null, array $providerResponse = null): void
    {
        $this->status = self::STATUS_FAILED;
        $this->status_message = $message;
        
        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }
        
        $this->save();
    }

    /**
     * Mark as cancelled
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Refund transaction
     */
    public function refund(string $reason = null): void
    {
        $this->status = self::STATUS_REFUNDED;
        $this->status_message = $reason;
        $this->save();

        // Handle refund logic (return coins, etc.)
        if ($this->coins_amount > 0 && $this->user) {
            $this->user->increment('coins', $this->coins_amount);
        }
    }
}
