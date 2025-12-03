<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseView extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'course_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'device_type',
        'viewed_date',
    ];

    protected function casts(): array
    {
        return [
            'viewed_date' => 'date',
        ];
    }

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('viewed_date', $date);
    }

    public function scopeByDeviceType($query, string $type)
    {
        return $query->where('device_type', $type);
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeUniqueVisitors($query)
    {
        return $query->distinct('user_id');
    }
}
