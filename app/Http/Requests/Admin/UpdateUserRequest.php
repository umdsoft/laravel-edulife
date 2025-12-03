<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/', Rule::unique('users')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:student,teacher,admin,super_admin'],
            'status' => ['required', 'in:active,inactive,blocked'],
        ];
    }

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
            'email.email' => 'Email formati noto\'g\'ri',
            'email.unique' => 'Bu email allaqachon ro\'yxatdan o\'tgan',
            'password.min' => 'Parol kamida :min ta belgidan iborat bo\'lishi kerak',
            'password.confirmed' => 'Parollar mos kelmadi',
            'role.required' => 'Rol tanlanishi shart',
            'role.in' => 'Tanlangan rol noto\'g\'ri',
            'status.required' => 'Status tanlanishi shart',
            'status.in' => 'Tanlangan status noto\'g\'ri',
        ];
    }
}
