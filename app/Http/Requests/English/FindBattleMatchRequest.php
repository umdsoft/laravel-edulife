<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class FindBattleMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'battle_type' => 'sometimes|string|in:ranked,casual,practice',
        ];
    }
}
