<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabComponent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'component_id',
        'name',
        'name_uz',
        'name_ru',
        'description',
        'description_uz',
        'category',
        'properties',
        'icon',
        'svg_content',
        'sprite_url',
        'sprite_frames',
        'color',
        'physics_body_type',
        'physics_config',
        'connection_points',
        'is_draggable',
        'is_rotatable',
        'is_resizable',
        'is_connectable',
        'is_configurable',
        'sounds',
        'is_active',
        'is_premium',
        'order_number',
    ];

    protected $casts = [
        'properties' => 'array',
        'physics_config' => 'array',
        'connection_points' => 'array',
        'sounds' => 'array',
        'is_draggable' => 'boolean',
        'is_rotatable' => 'boolean',
        'is_resizable' => 'boolean',
        'is_connectable' => 'boolean',
        'is_configurable' => 'boolean',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
        'sprite_frames' => 'integer',
        'order_number' => 'integer',
    ];

    protected $attributes = [
        'is_draggable' => true,
        'is_rotatable' => false,
        'is_resizable' => false,
        'is_connectable' => true,
        'is_configurable' => true,
        'is_active' => true,
        'is_premium' => false,
        'sprite_frames' => 1,
        'order_number' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const CATEGORIES = [
        'electrical_source' => [
            'label' => 'Tok manbalari',
            'icon' => 'bolt',
            'color' => '#F59E0B',
        ],
        'electrical_passive' => [
            'label' => 'Passiv elementlar',
            'icon' => 'square-3-stack-3d',
            'color' => '#6B7280',
        ],
        'electrical_output' => [
            'label' => 'Chiqish elementlari',
            'icon' => 'light-bulb',
            'color' => '#FBBF24',
        ],
        'electrical_control' => [
            'label' => 'Boshqaruv',
            'icon' => 'adjustments-horizontal',
            'color' => '#3B82F6',
        ],
        'electrical_measure' => [
            'label' => "O'lchov asboblari",
            'icon' => 'chart-bar',
            'color' => '#10B981',
        ],
        'mechanical_body' => [
            'label' => 'Jismlar',
            'icon' => 'cube',
            'color' => '#8B5CF6',
        ],
        'mechanical_support' => [
            'label' => 'Tayanch va bog\'lamlar',
            'icon' => 'link',
            'color' => '#EC4899',
        ],
        'optical_source' => [
            'label' => "Yorug'lik manbalari",
            'icon' => 'sun',
            'color' => '#FCD34D',
        ],
        'optical_element' => [
            'label' => 'Optik elementlar',
            'icon' => 'eye',
            'color' => '#06B6D4',
        ],
        'optical_target' => [
            'label' => 'Ekran va maqsadlar',
            'icon' => 'computer-desktop',
            'color' => '#A78BFA',
        ],
        'measurement_tool' => [
            'label' => "O'lchov asboblari",
            'icon' => 'scale',
            'color' => '#14B8A6',
        ],
        'thermal_source' => [
            'label' => 'Issiqlik manbalari',
            'icon' => 'fire',
            'color' => '#EF4444',
        ],
        'thermal_container' => [
            'label' => 'Idishlar',
            'icon' => 'beaker',
            'color' => '#64748B',
        ],
    ];

    public const PHYSICS_BODY_TYPES = [
        'static' => "Qo'zg'almas",
        'dynamic' => "Dinamik",
        'kinematic' => "Kinematik",
        'sensor' => "Sensor",
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('order_number');
    }

    public function scopeElectrical($query)
    {
        return $query->where('category', 'like', 'electrical_%');
    }

    public function scopeMechanical($query)
    {
        return $query->where('category', 'like', 'mechanical_%');
    }

    public function scopeOptical($query)
    {
        return $query->where('category', 'like', 'optical_%');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_uz ?? $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_uz ?? $this->description;
    }

    public function getCategoryInfoAttribute(): array
    {
        return self::CATEGORIES[$this->category] ?? [
            'label' => $this->category,
            'icon' => 'cube',
            'color' => '#6B7280',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category_info['label'];
    }

    public function getCategoryColorAttribute(): string
    {
        return $this->category_info['color'];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get property value with defaults
     */
    public function getProperty(string $key, $default = null)
    {
        $properties = $this->properties ?? [];
        $prop = $properties[$key] ?? null;
        
        if (!$prop) return $default;
        
        return match($prop['type'] ?? 'fixed') {
            'fixed' => $prop['value'] ?? $default,
            'range' => $prop['default'] ?? $prop['min'] ?? $default,
            default => $default,
        };
    }

    /**
     * Get property range info
     */
    public function getPropertyRange(string $key): ?array
    {
        $properties = $this->properties ?? [];
        $prop = $properties[$key] ?? null;
        
        if (!$prop || ($prop['type'] ?? '') !== 'range') {
            return null;
        }
        
        return [
            'min' => $prop['min'] ?? 0,
            'max' => $prop['max'] ?? 100,
            'default' => $prop['default'] ?? $prop['min'] ?? 0,
            'step' => $prop['step'] ?? 1,
            'unit' => $prop['unit'] ?? '',
        ];
    }

    /**
     * Get connection point by ID
     */
    public function getConnectionPoint(string $id): ?array
    {
        $points = $this->connection_points ?? [];
        
        foreach ($points as $point) {
            if (($point['id'] ?? '') === $id) {
                return $point;
            }
        }
        
        return null;
    }

    /**
     * Export component for simulation canvas
     */
    public function toSimulationData(): array
    {
        return [
            'id' => $this->component_id,
            'name' => $this->localized_name,
            'category' => $this->category,
            'properties' => $this->properties,
            'svg' => $this->svg_content,
            'sprite' => $this->sprite_url,
            'spriteFrames' => $this->sprite_frames,
            'physicsType' => $this->physics_body_type,
            'physicsConfig' => $this->physics_config,
            'connectionPoints' => $this->connection_points,
            'draggable' => $this->is_draggable,
            'rotatable' => $this->is_rotatable,
            'resizable' => $this->is_resizable,
            'connectable' => $this->is_connectable,
            'configurable' => $this->is_configurable,
            'sounds' => $this->sounds,
        ];
    }
}
