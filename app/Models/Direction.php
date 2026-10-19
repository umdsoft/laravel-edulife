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

    // Pages read direction.name from serialized models
    protected $appends = ['name'];

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

    public function olympiadTypes()
    {
        return $this->hasMany(OlympiadType::class);
    }

    // UI is Uzbek; Russian is the only other translated locale
    public function getNameAttribute(): string
    {
        $name = app()->getLocale() === 'ru' ? $this->name_ru : null;

        return $name ?: (string) $this->name_uz;
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
