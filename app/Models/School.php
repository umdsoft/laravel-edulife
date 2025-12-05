<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'district_id',
        'name',
        'name_uz',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function region(): BelongsTo
    {
        return $this->district->region();
    }

    public function olympiads(): HasMany
    {
        return $this->hasMany(Olympiad::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(OlympiadRegistration::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    // ==================== ACCESSORS ====================

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'uz' ? $this->name_uz : $this->name;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [$this->localized_name];
        
        if ($this->district) {
            $parts[] = $this->district->localized_name;
            if ($this->district->region) {
                $parts[] = $this->district->region->localized_name;
            }
        }
        
        return implode(', ', $parts);
    }
}
