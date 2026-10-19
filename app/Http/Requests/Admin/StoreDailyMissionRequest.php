<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyMissionRequest extends FormRequest
{
    /**
     * Form type values => daily_missions.type enum values.
     */
    public const TYPE_MAP = [
        'lessons' => 'complete_lessons',
        'tests' => 'pass_tests',
        'battles' => 'play_battles',
        'battle_wins' => 'win_battles',
        'watch_time' => 'watch_time',
        'login' => 'daily_login',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // resource route parameter is {daily_mission}
        $mission = $this->route('daily_mission') ?? $this->route('dailyMission');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('daily_missions')->ignore($mission?->id)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:300'],
            'icon' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(array_keys(self::TYPE_MAP))],
            'target_count' => ['required', 'integer', 'min:1'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->is_active ?? true,
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    /**
     * Validated data mapped to the daily_missions columns
     * (the form posts short type keys; the table stores the type enum).
     */
    public function missionAttributes(): array
    {
        $data = $this->validated();
        $data['type'] = self::TYPE_MAP[$data['type']];

        return $data;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kod kiritilishi shart',
            'title.required' => 'Sarlavha kiritilishi shart',
            'type.required' => 'Tur tanlanishi shart',
            'target_count.required' => 'Maqsad kiritilishi shart',
        ];
    }
}
