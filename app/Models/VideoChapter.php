<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoChapter extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'video_id',
        'lesson_id',
        'title',
        'timestamp',
        'description',
        'sort_order',
    ];
    
    protected $casts = [
        'timestamp' => 'integer',
        'sort_order' => 'integer',
    ];
    
    protected $appends = ['formatted_timestamp'];
    
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    public function getFormattedTimestampAttribute(): string
    {
        $minutes = floor($this->timestamp / 60);
        $seconds = $this->timestamp % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
