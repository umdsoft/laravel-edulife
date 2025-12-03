<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'uppercase', 'unique:promo_codes,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto uppercase the code
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }

        // Set defaults
        $this->merge([
            'max_uses_per_user' => $this->max_uses_per_user ?? 1,
            'is_active' => $this->is_active ?? true,
        ]);
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kod kiritilishi shart',
            'code.unique' => 'Bu kod allaqachon mavjud',
            'type.required' => 'Tur kiritilishi shart',
            'value.required' => 'Qiymat kiritilishi shart',
        ];
    }
}
