<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'specializations',
        'website_url',
        'youtube_url',
        'telegram_url',
        'linkedin_url',
        'avg_rating',
        'total_reviews',
        'total_students',
        'total_courses',
        'is_verified',
        'verified_at',
        'expertise',
        'experience_years',
    ];

    protected function casts(): array
    {
        return [
            'specializations' => 'array',
            'expertise' => 'array',
            'experience_years' => 'integer',
            'avg_rating' => 'decimal:2',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id', 'user_id');
    }
}
