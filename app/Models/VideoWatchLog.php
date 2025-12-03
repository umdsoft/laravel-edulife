<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoWatchLog extends Model
{
    use HasUuids;
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'lesson_id',
        'video_id',
        'start_position',
        'end_position',
        'duration',
        'quality',
        'playback_rate',
        'device_type',
        'started_at',
        'ended_at',
    ];
    
    protected $casts = [
        'start_position' => 'integer',
        'end_position' => 'integer',
        'duration' => 'integer',
        'playback_rate' => 'float',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
