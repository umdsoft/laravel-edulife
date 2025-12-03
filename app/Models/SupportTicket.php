<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'ticket_number', 'subject', 'description',
        'category', 'priority', 'status', 'assigned_to',
        'first_response_at', 'resolved_at', 'closed_at',
        'satisfaction_rating', 'feedback',
    ];
    
    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'satisfaction_rating' => 'integer',
    ];
    
    protected $appends = ['status_label', 'priority_label', 'category_label'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }
    
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf("TICKET-%s-%05d", $year, $count);
    }
    
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Ochiq',
            'in_progress' => 'Ko\'rib chiqilmoqda',
            'waiting_user' => 'Javob kutilmoqda',
            'resolved' => 'Hal qilindi',
            'closed' => 'Yopilgan',
            default => $this->status,
        };
    }
    
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Past',
            'medium' => 'O\'rta',
            'high' => 'Yuqori',
            'urgent' => 'Shoshilinch',
            default => $this->priority,
        };
    }
    
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'technical' => 'Texnik muammo',
            'payment' => 'To\'lov',
            'course' => 'Kurs haqida',
            'account' => 'Hisob',
            'other' => 'Boshqa',
            default => $this->category,
        };
    }
}
