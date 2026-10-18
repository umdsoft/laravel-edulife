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
        // resource route parameter is {daily_mission}
        $missionId = $this->route('daily_mission')?->id;

        return [
            'code' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('daily_missions')->ignore($missionId)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:300'],
            'icon' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:lessons,tests,battles,battle_wins,watch_time,login,streak'],
            'target_count' => ['required', 'integer', 'min:1'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->is_active ?? true,
        ]);
    }

    /**
     * Validated data mapped to the daily_missions columns
     * (the form posts type/target_count; the table stores task_type/target_value).
     */
    public function missionAttributes(): array
    {
        $data = $this->validated();
        $data['task_type'] = $data['type'];
        $data['target_value'] = $data['target_count'];
        unset($data['type'], $data['target_count']);

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
