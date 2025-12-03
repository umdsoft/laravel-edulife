<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_uz' => ['required', 'string', 'max:100'],
            'name_ru' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:directions,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'max:7'], // hex color
        ];
    }

    public function messages(): array
    {
        return [
            'name_uz.required' => 'O\'zbek nomi kiritilishi shart',
            'name_ru.required' => 'Rus nomi kiritilishi shart',
            'name_en.required' => 'Ingliz nomi kiritilishi shart',
            'slug.required' => 'Slug kiritilishi shart',
            'slug.unique' => 'Bu slug allaqachon mavjud',
            'icon.required' => 'Icon tanlanishi shart',
            'color.required' => 'Rang tanlanishi shart',
        ];
    }
}
