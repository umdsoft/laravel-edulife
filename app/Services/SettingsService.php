<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SettingsAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    /**
     * Update setting with audit log
     */
    public function update(string $key, mixed $value): bool
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $oldValue = $setting->value;

        // Convert value for storage
        if (is_bool($value)) {
            $newValue = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $newValue = json_encode($value);
        } else {
            $newValue = (string) $value;
        }

        // Update setting
        $setting->update(['value' => $newValue]);

        // Clear cache
        Cache::forget("setting.{$key}");

        // Audit log
        $this->logChange($key, $oldValue, $newValue);

        return true;
    }

    /**
     * Log setting change
     */
    protected function logChange(string $key, ?string $oldValue, ?string $newValue): void
    {
        if (Auth::check()) {
            SettingsAuditLog::create([
                'user_id' => Auth::id(),
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        }
    }

    /**
     * Get all English settings
     */
    public function getEnglishSettings(): array
    {
        return [
            'test_mode' => $this->get('english_test_mode', false),
            'require_80_percent' => $this->get('english_require_80_percent', true),
            'max_xp_per_lesson' => $this->get('english_max_xp_per_lesson', 10),
            'max_coins_per_lesson' => $this->get('english_max_coins_per_lesson', 5),
        ];
    }

    /**
     * Toggle test mode
     */
    public function toggleTestMode(): bool
    {
        $currentValue = $this->get('english_test_mode', false);
        return $this->update('english_test_mode', !$currentValue);
    }

    /**
     * Check if test mode is enabled
     */
    public function isTestMode(): bool
    {
        return (bool) $this->get('english_test_mode', false);
    }
}
