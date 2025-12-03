<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ism kiritilishi shart',
            'first_name.max' => 'Ism juda uzun',
            'last_name.required' => 'Familiya kiritilishi shart',
            'last_name.max' => 'Familiya juda uzun',
            'phone.required' => 'Telefon raqam kiritilishi shart',
            'phone.regex' => 'Telefon raqam +998XXXXXXXXX formatida bo\'lishi kerak',
            'phone.unique' => 'Bu telefon raqam allaqachon ro\'yxatdan o\'tgan',
            'password.required' => 'Parol kiritilishi shart',
            'password.min' => 'Parol kamida :min ta belgidan iborat bo\'lishi kerak',
            'password.confirmed' => 'Parollar mos kelmadi',
        ];
    }
}
