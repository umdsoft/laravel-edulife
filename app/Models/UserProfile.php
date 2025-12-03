<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'birth_date',
        'gender',
        'country',
        'city',
        'bio',
        'timezone',
        'language',
        'notification_settings',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'notification_settings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
