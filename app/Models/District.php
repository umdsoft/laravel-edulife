<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'region_id',
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

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
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

    public function scopeInRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    // ==================== ACCESSORS ====================

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'uz' ? $this->name_uz : $this->name;
    }

    public function getFullNameAttribute(): string
    {
        return $this->localized_name . ', ' . $this->region->localized_name;
    }
}
