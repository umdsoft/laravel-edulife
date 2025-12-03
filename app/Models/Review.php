<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_id',
        'rating',
        'title',
        'review_text',
        'pros',
        'cons',
        'status',
        'helpful_count',
        'admin_reply',
        'replied_at',
        'teacher_reply',
        'teacher_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'pros' => 'array',
            'cons' => 'array',
            'replied_at' => 'datetime',
            'teacher_replied_at' => 'datetime',
        ];
    }

    // Accessors
    public function getContentAttribute(): string
    {
        return $this->review_text;
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

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper Methods
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
        $this->course->updateRating();
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }
}
