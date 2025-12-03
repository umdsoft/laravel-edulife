<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'participant1_id',
        'participant2_id',
        'course_id',
        'last_message_id',
        'participant1_unread_count',
        'participant2_unread_count',
        'participant1_last_read_at',
        'participant2_last_read_at',
        'participant1_deleted',
        'participant2_deleted',
    ];

    protected function casts(): array
    {
        return [
            'participant1_last_read_at' => 'datetime',
            'participant2_last_read_at' => 'datetime',
            'participant1_deleted' => 'boolean',
            'participant2_deleted' => 'boolean',
        ];
    }

    // Relationships
    public function participant1()
    {
        return $this->belongsTo(User::class, 'participant1_id');
    }

    public function participant2()
    {
        return $this->belongsTo(User::class, 'participant2_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lastMessage()
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Helper Methods
    public function getUnreadCountFor(User $user): int
    {
        if ($user->id === $this->participant1_id) {
            return $this->participant1_unread_count;
        } elseif ($user->id === $this->participant2_id) {
            return $this->participant2_unread_count;
        }
        return 0;
    }

    public function markAsReadBy(User $user): void
    {
        if ($user->id === $this->participant1_id) {
            $this->update([
                'participant1_unread_count' => 0,
                'participant1_last_read_at' => now(),
            ]);
        } elseif ($user->id === $this->participant2_id) {
            $this->update([
                'participant2_unread_count' => 0,
                'participant2_last_read_at' => now(),
            ]);
        }
    }

    public function incrementUnreadFor(User $user): void
    {
        if ($user->id === $this->participant1_id) {
            $this->increment('participant1_unread_count');
        } elseif ($user->id === $this->participant2_id) {
            $this->increment('participant2_unread_count');
        }
    }

    public function isDeletedBy(User $user): bool
    {
        if ($user->id === $this->participant1_id) {
            return $this->participant1_deleted;
        } elseif ($user->id === $this->participant2_id) {
            return $this->participant2_deleted;
        }
        return false;
    }

    public function deleteFor(User $user): void
    {
        if ($user->id === $this->participant1_id) {
            $this->update(['participant1_deleted' => true]);
        } elseif ($user->id === $this->participant2_id) {
            $this->update(['participant2_deleted' => true]);
        }
    }
}
