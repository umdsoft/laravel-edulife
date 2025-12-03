<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('achievements')->ignore($this->route('achievement'))],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'icon' => ['required', 'string', 'max:10'],
            'category' => ['required', 'in:learning,testing,battle,tournament,streak,social,special'],
            'rarity' => ['required', 'in:common,rare,epic,legendary'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'requirements' => ['nullable', 'json'],
            'is_hidden' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kod kiritilishi shart',
            'code.unique' => 'Bu kod allaqachon mavjud',
            'title.required' => 'Sarlavha kiritilishi shart',
        ];
    }
}
