<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'phone',
        'message',
        'type',
        'provider',
        'status',
        'provider_message_id',
        'provider_response',
        'error_message',
        'cost',
        'sent_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'cost' => 'decimal:2',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
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
}
