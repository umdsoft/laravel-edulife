<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit a tournament from the admin panel. Times are datetime-local
 * values in the app timezone (Asia/Tashkent).
 */
class StoreTournamentRequest extends FormRequest
{
    public const TYPES = ['weekly', 'monthly', 'seasonal', 'special'];

    public const STATUSES = ['draft', 'registration', 'in_progress', 'finished', 'cancelled'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'direction_id' => ['required', 'uuid', Rule::exists('directions', 'id')],
            'type' => ['required', Rule::in(self::TYPES)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'entry_fee_coins' => ['required', 'integer', 'min:0', 'max:100000'],
            'prize_pool_coins' => ['required', 'integer', 'min:0', 'max:10000000'],
            'min_participants' => ['required', 'integer', 'min:2', 'max:1024'],
            'max_participants' => ['required', 'integer', 'gte:min_participants', 'max:1024'],
            'min_elo' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'max_elo' => ['nullable', 'integer', 'min:0', 'max:5000', 'gte:min_elo'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after:registration_starts_at'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'questions_per_match' => ['required', 'integer', 'min:1', 'max:50'],
            'time_per_question' => ['required', 'integer', 'min:5', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_participants.gte' => "Maksimal ishtirokchilar minimaldan kam bo'lmasligi kerak.",
            'max_elo.gte' => "Maksimal ELO minimal ELO dan kam bo'lmasligi kerak.",
            'registration_ends_at.after' => "Ro'yxat tugashi boshlanishidan keyin bo'lishi kerak.",
            'ends_at.after' => "Tugash vaqti boshlanish vaqtidan keyin bo'lishi kerak.",
        ];
    }
}
