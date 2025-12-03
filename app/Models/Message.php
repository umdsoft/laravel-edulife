<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'type',
        'attachment_url',
        'attachment_name',
        'attachment_size',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // Accessors
    public function getAttachmentUrlFullAttribute(): ?string
    {
        return $this->attachment_url ? asset('storage/' . $this->attachment_url) : null;
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->attachment_size) return '';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->attachment_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($message) {
            // Update conversation's last message
            $message->conversation->update(['last_message_id' => $message->id]);
            
            // Increment unread count for receiver
            $conversation = $message->conversation;
            $receiver = $message->sender_id === $conversation->participant1_id 
                ? $conversation->participant2 
                : $conversation->participant1;
                
            $conversation->incrementUnreadFor($receiver);
        });
    }
}
