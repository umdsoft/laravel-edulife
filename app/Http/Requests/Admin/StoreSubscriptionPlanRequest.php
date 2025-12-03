<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan') ? $this->route('plan')->id : null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('subscription_plans')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'interval' => ['required', 'in:month,year'],
            'interval_count' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Reja nomi kiritilishi shart',
            'slug.required' => 'Slug kiritilishi shart',
            'slug.unique' => 'Bu slug allaqachon mavjud',
            'price.required' => 'Narx kiritilishi shart',
            'price.numeric' => 'Narx raqam bo\'lishi kerak',
            'interval.required' => 'Davr kiritilishi shart',
            'interval.in' => 'Davr faqat month yoki year bo\'lishi mumkin',
        ];
    }
}
