<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleProgress extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'module_id',
        'enrollment_id',
        'progress',
        'completed_lessons',
        'total_lessons',
        'test_passed',
        'test_score',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'decimal:2',
            'test_passed' => 'boolean',
            'test_score' => 'decimal:2',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Helper Methods
    public function updateProgress(): void
    {
        $completedLessons = LessonProgress::where('enrollment_id', $this->enrollment_id)
            ->whereIn('lesson_id', function($query) {
                $query->select('id')
                    ->from('lessons')
                    ->where('module_id', $this->module_id);
            })
            ->where('is_completed', true)
            ->count();
            
        $this->update([
            'completed_lessons' => $completedLessons,
            'progress' => $this->total_lessons > 0 
                ? ($completedLessons / $this->total_lessons) * 100 
                : 0,
        ]);
        
        if ($completedLessons >= $this->total_lessons && !$this->is_completed) {
            $this->markAsCompleted();
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
