<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAttemptLog extends Model
{
    use HasUuids;
    
    public $timestamps = false;
    
    protected $fillable = [
        'attempt_id',
        'event_type',
        'event_data',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];
    
    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];
    
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }
}
