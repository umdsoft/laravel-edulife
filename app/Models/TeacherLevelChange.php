<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLevelChange extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'teacher_id',
        'old_level',
        'new_level',
        'old_score',
        'new_score',
        'reason',
        'changed_by',
    ];
    
    protected $casts = [
        'old_score' => 'decimal:2',
        'new_score' => 'decimal:2',
    ];
    
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
    
    public function isAutomatic(): bool
    {
        return is_null($this->changed_by);
    }
    
    public function isUpgrade(): bool
    {
        $levels = ['new' => 1, 'verified' => 2, 'featured' => 3, 'top' => 4];
        return ($levels[$this->new_level] ?? 0) > ($levels[$this->old_level] ?? 0);
    }
}
