<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'course_id',
        'subscription_plan_id',
        'amount',
        'original_amount',
        'discount_amount',
        'currency',
        'status',
        'provider',
        'provider_transaction_id',
        'provider_response',
        'promo_code_id',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'provider_response' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function enrollment()
    {
        return $this->hasOne(Enrollment::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'last_payment_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // Helper Methods
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
        
        // Create enrollment if course purchase
        if ($this->type === 'course_purchase' && $this->course_id) {
            $this->user->enrollments()->create([
                'course_id' => $this->course_id,
                'access_type' => 'purchase',
                'payment_id' => $this->id,
                'total_lessons' => $this->course->lessons_count,
            ]);
            
            $this->course->incrementStudentsCount();
        }
    }

    public function refund(string $reason): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $this->amount,
            'refund_reason' => $reason,
        ]);
        
        // Update enrollment status
        $this->enrollment?->update(['status' => 'refunded']);
    }
}
