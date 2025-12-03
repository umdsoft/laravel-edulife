<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherScoreHistory extends Model
{
    use HasUuids;
    
    protected $table = 'teacher_score_history';
    
    protected $fillable = [
        'teacher_id',
        'score',
        'level',
        'breakdown',
        'calculated_date',
    ];
    
    protected $casts = [
        'score' => 'decimal:2',
        'breakdown' => 'array',
        'calculated_date' => 'date',
    ];
    
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
