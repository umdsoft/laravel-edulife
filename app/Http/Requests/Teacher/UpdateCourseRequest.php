<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'direction_id' => ['required', 'uuid', 'exists:directions,id'],
            'description' => ['required', 'string', 'min:100'],
            'short_description' => ['required', 'string', 'max:200'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'size:2'],
            'price' => ['required_without:is_free', 'nullable', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'lt:price'],
            'is_free' => ['boolean'],
            'requirements' => ['nullable', 'array'],
            'what_will_learn' => ['nullable', 'array'],
            'who_is_for' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
