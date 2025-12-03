<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'email',
        'subject',
        'template',
        'type',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'error_message',
        'sent_at',
        'opened_at',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    // Accessors
    public function getWasOpenedAttribute(): bool
    {
        return $this->opened_at !== null;
    }

    public function getWasClickedAttribute(): bool
    {
        return $this->clicked_at !== null;
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

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
