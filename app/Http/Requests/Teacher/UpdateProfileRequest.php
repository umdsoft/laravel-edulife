<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email,' . auth()->id()],
            'bio' => ['nullable', 'string', 'max:2000'],
            'headline' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'array'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'website' => ['nullable', 'url'],
            'social_links' => ['nullable', 'array'],
        ];
    }
}
