<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'name_uz',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function schools(): HasMany
    {
        return $this->hasManyThrough(School::class, District::class);
    }

    public function olympiads(): HasMany
    {
        return $this->hasMany(Olympiad::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== ACCESSORS ====================

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'uz' ? $this->name_uz : $this->name;
    }
}
