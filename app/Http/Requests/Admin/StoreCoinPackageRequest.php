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
            // decimal columns: "15000.00" from the edit form is valid
            'price' => ['required', 'numeric', 'min:0'],
            // the crossed-out price must not be below the price (equal = no discount)
            'original_price' => ['nullable', 'numeric', 'gte:price'],
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
            'original_price.gte' => "Asl narx joriy narxdan kam bo'lmasligi kerak",
        ];
    }
}
