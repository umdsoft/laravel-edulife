<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherBankAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'bank_name',
        'account_number',
        'card_number',
        'card_holder_name',
        'is_primary',
        'is_verified',
        'verified_at',
    ];

    protected $hidden = [
        'account_number',
        'card_number',
    ];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'card_number' => 'encrypted',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    // Accessors
    public function getMaskedCardNumberAttribute(): ?string
    {
        if (!$this->card_number) return null;
        $lastFour = substr($this->card_number, -4);
        return "**** **** **** {$lastFour}";
    }

    public function getMaskedAccountNumberAttribute(): ?string
    {
        if (!$this->account_number) return null;
        $lastFour = substr($this->account_number, -4);
        return "****{$lastFour}";
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function payouts()
    {
        return $this->hasMany(TeacherPayout::class, 'bank_account_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Helper Methods
    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function makePrimary(): void
    {
        // Unset other primary accounts
        static::where('teacher_id', $this->teacher_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
            
        $this->update(['is_primary' => true]);
    }
}
