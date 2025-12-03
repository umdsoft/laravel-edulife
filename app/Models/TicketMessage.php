<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'ticket_id', 'user_id', 'content',
        'is_from_support', 'is_internal_note',
        'attachments', 'is_read', 'read_at',
    ];
    
    protected $casts = [
        'is_from_support' => 'boolean',
        'is_internal_note' => 'boolean',
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
    
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
