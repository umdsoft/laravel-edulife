<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $missionId = $this->route('dailyMission') ? $this->route('dailyMission')->id : null;

        return [
            'code' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('daily_missions')->ignore($missionId)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:300'],
            'icon' => ['required', 'string', 'max:10'],
            'type' => ['required', 'in:lessons,tests,battles,battle_wins,watch_time,login,streak'],
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
