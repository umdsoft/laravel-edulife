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
            \App\Models\AuditLog::log('updated', null, [$key => $oldValue], [$key => $newValue], "Sozlama o'zgartirildi: {$key}");

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
}
