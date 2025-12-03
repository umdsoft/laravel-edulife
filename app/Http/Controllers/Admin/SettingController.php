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

        foreach ($validated['settings'] as $settingData) {
            Setting::where('key', $settingData['key'])->update([
                'value' => $settingData['value'],
            ]);
        }

        // Clear settings cache
        Cache::forget('settings');

        return back()->with('success', 'Sozlamalar saqlandi!');
    }
}
