<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The edit modal changes the text, type and pin only; publishing date stays.
 */
class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:20', 'max:10000'],
            'type' => ['required', 'in:info,update,important,promotion'],
            'is_pinned' => ['boolean'],
        ];
    }
}
