<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class StartLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_id' => 'required|uuid|exists:english_lessons,id',
        ];
    }
}
