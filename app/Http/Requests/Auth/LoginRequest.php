<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // email or phone
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email yoki telefon raqam kiritilishi shart',
            'password.required' => 'Parol kiritilishi shart',
            'password.min' => 'Parol kamida :min ta belgidan iborat bo\'lishi kerak',
        ];
    }
}
