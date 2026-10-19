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
        // the form always sends is_free (true/false), so required_without:is_free never fired
        $free = $this->boolean('is_free');

        return [
            'title' => ['required', 'string', 'max:255'],
            'direction_id' => ['required', 'uuid', 'exists:directions,id'],
            'description' => ['required', 'string', 'min:100'],
            'short_description' => ['required', 'string', 'max:200'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'size:2'],
            'price' => $free ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'gt:0'],
            'discount_price' => $free ? ['nullable'] : ['nullable', 'numeric', 'gt:0', 'lt:price'],
            'is_free' => ['boolean'],
            'requirements' => ['nullable', 'array'],
            'what_will_learn' => ['nullable', 'array'],
            'who_is_for' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
