<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:achievements,code'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_hidden' => $this->is_hidden ?? false,
            'is_active' => $this->is_active ?? true,
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kod kiritilishi shart',
            'code.unique' => 'Bu kod allaqachon mavjud',
            'title.required' => 'Sarlavha kiritilishi shart',
            'category.required' => 'Kategoriya tanlanishi shart',
            'rarity.required' => 'Kamyoblik darajasi tanlanishi shart',
        ];
    }
}
