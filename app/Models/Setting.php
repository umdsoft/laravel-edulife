<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Setting::get() caches each key for an hour: any write to a row drops
     * that key, so the next read (e.g. a reward) sees the new value.
     */
    protected static function booted(): void
    {
        $forget = function (Setting $setting) {
            Cache::forget("setting.{$setting->key}");
            if ($setting->isDirty('key') || $setting->wasChanged('key')) {
                Cache::forget('setting.'.$setting->getOriginal('key'));
            }
            Cache::forget('settings');
        };

        static::saved($forget);
        static::deleted($forget);
    }

    // Get typed value
    public function getTypedValueAttribute()
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    // Static helper to get setting
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    // Static helper to set setting
    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]
        );
        Cache::forget("setting.{$key}");
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
