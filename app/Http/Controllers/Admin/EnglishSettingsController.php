<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SettingsAuditLog;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnglishSettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Settings sahifasi
     */
    public function index()
    {
        $settings = Setting::where('group', 'english')->get();

        $auditLogs = SettingsAuditLog::where('setting_key', 'like', 'english_%')
            ->with('user:id,first_name,last_name,email')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('Admin/English/Settings', [
            'settings' => $settings->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->typed_value,
                    'type' => $setting->type,
                    'label' => $setting->label,
                    'description' => $setting->description,
                ];
            }),
            'auditLogs' => $auditLogs->map(function ($log) {
                $userName = $log->user
                    ? trim($log->user->first_name . ' ' . $log->user->last_name) ?: $log->user->email
                    : 'Noma\'lum';
                return [
                    'id' => $log->id,
                    'user' => $userName,
                    'setting_key' => $log->setting_key,
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('d.m.Y H:i'),
                    'time_ago' => $log->created_at->diffForHumans(),
                ];
            }),
            'isTestMode' => $this->settingsService->isTestMode(),
        ]);
    }

    /**
     * Test mode toggle
     */
    public function toggleTestMode(Request $request)
    {
        $this->settingsService->toggleTestMode();

        $isTestMode = $this->settingsService->isTestMode();

        return back()->with(
            'success',
            $isTestMode
            ? '🔓 Test rejimi YOQILDI! Barcha darslar ochiq.'
            : '🔒 Test rejimi O\'CHIRILDI! Darslar asl holatida.'
        );
    }

    /**
     * Update single setting
     */
    public function update(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => 'required',
        ]);

        // Type-specific casting
        $value = match ($setting->type) {
            'boolean' => filter_var($validated['value'], FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $validated['value'],
            default => $validated['value'],
        };

        $this->settingsService->update($key, $value);

        return back()->with('success', "✅ {$setting->label} yangilandi!");
    }

    /**
     * Batch update settings
     */
    public function batchUpdate(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $item) {
            $this->settingsService->update($item['key'], $item['value']);
        }

        return back()->with('success', '✅ Sozlamalar saqlandi!');
    }

    /**
     * Get audit logs
     */
    public function auditLogs(Request $request)
    {
        $logs = SettingsAuditLog::where('setting_key', 'like', 'english_%')
            ->with('user:id,name,email')
            ->latest()
            ->paginate(50);

        return Inertia::render('Admin/English/AuditLogs', [
            'logs' => $logs,
        ]);
    }
}
