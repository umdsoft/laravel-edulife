<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoinPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'coins' => ['required', 'integer', 'min:1'],
            'bonus_coins' => ['integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bonus_coins' => $this->bonus_coins ?? 0,
            'is_featured' => $this->is_featured ?? false,
            'is_active' => $this->is_active ?? true,
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nom kiritilishi shart',
            'coins.required' => 'COIN miqdori kiritilishi shart',
            'price.required' => 'Narx kiritilishi shart',
        ];
    }
}
