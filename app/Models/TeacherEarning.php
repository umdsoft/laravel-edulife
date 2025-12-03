<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherEarning extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'teacher_id',
        'course_id',
        'payment_id',
        'type',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'status',
        'payout_id',
        'period',
        'watch_minutes',
        'pool_share',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'pool_share' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payout()
    {
        return $this->belongsTo(TeacherPayout::class, 'payout_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function markAsPaid(TeacherPayout $payout): void
    {
        $this->update([
            'status' => 'paid',
            'payout_id' => $payout->id,
        ]);
    }
}
