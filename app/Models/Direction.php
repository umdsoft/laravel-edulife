<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name_uz',
        'name_ru',
        'name_en',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'courses_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function battles()
    {
        return $this->hasMany(Battle::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    // Get name by locale
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        $field = "name_{$locale}";
        return $this->{$field} ?? $this->name_uz;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
