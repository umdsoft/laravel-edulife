<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'setting_key',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setting()
    {
        return $this->belongsTo(Setting::class, 'setting_key', 'key');
    }
}
