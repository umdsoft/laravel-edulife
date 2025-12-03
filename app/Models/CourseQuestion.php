<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseQuestion extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $table = 'course_qna';
    
    protected $fillable = [
        'course_id',
        'lesson_id',
        'user_id',
        'parent_id',
        'content',
        'is_answered',
        'is_pinned',
        'is_highlighted',
        'upvotes',
        'answered_at',
    ];
    
    protected $casts = [
        'is_answered' => 'boolean',
        'is_pinned' => 'boolean',
        'is_highlighted' => 'boolean',
        'upvotes' => 'integer',
        'answered_at' => 'datetime',
    ];
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }
    
    public function scopeQuestions($query)
    {
        return $query->whereNull('parent_id');
    }
    
    public function scopeUnanswered($query)
    {
        return $query->where('is_answered', false);
    }
    
    public function markAsAnswered(): void
    {
        $this->update([
            'is_answered' => true,
            'answered_at' => now(),
        ]);
    }
}
