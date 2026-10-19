<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display all settings.
     */
    public function index(): Response
    {
        $settings = Setting::all()->groupBy('group')->map(function ($group) {
            return $group->mapWithKeys(function ($setting) {
                return [$setting->key => [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                ]];
            });
        });

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        $service = app(\App\Services\SettingsService::class);
        $unknown = [];

        foreach ($validated['settings'] as $settingData) {
            $key = $this->resolveKey($settingData['key']);
            if ($key === null) {
                $unknown[] = $settingData['key'];

                continue;
            }

            // casts, per-key cache and audit log
            $service->update($key, $settingData['value']);
        }

        Cache::forget('settings');

        if ($unknown) {
            return back()->with('error', "Ba'zi sozlamalar topilmadi va saqlanmadi: ".implode(', ', $unknown));
        }

        return back()->with('success', 'Sozlamalar saqlandi!');
    }

    /**
     * The settings page sends "group.key" (xp.xp_per_lesson); the table stores
     * the bare key (xp_per_lesson). Accept both.
     */
    private function resolveKey(string $key): ?string
    {
        if (Setting::where('key', $key)->exists()) {
            return $key;
        }

        if (str_contains($key, '.')) {
            [$group, $bare] = explode('.', $key, 2);
            if (Setting::where('key', $bare)->where('group', $group)->exists()) {
                return $bare;
            }
        }

        return null;
    }
}
