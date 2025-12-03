<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPayout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'teacher_id',
        'bank_account_id',
        'amount',
        'status',
        'transaction_id',
        'admin_note',
        'failure_reason',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(TeacherBankAccount::class, 'bank_account_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function earnings()
    {
        return $this->hasMany(TeacherEarning::class, 'payout_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper Methods
    public function markAsCompleted(string $transactionId, User $processor): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);
        
        // Mark all earnings as paid
        $this->earnings()->update(['status' => 'paid']);
    }

    public function markAsFailed(string $reason, User $processor): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);
    }
}
