<?php

namespace Tests\Concerns;

use App\Models\Direction;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadRegistration;
use App\Models\OlympiadType;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesOlympiads
{
    protected function olympiadType(): OlympiadType
    {
        $direction = Direction::firstOrCreate(['slug' => 'math'], ['name_uz' => 'Matematika']);

        return OlympiadType::forceCreate([
            'direction_id' => $direction->id, 'name' => 'math-'.Str::random(4), 'display_name' => 'Mathematics',
            'display_name_uz' => 'Matematika', 'sections' => ['test'], 'section_weights' => ['test' => 100], 'is_active' => true,
        ]);
    }

    protected function olympiad(array $attributes = []): Olympiad
    {
        return Olympiad::forceCreate(array_merge([
            'title' => 'Matematika olimpiadasi',
            'slug' => 'olimpiada-'.Str::random(6),
            'olympiad_type_id' => $this->olympiadType()->id,
            'sections_config' => [],
            'total_duration_minutes' => 60,
            'total_max_points' => 100,
            'status' => 'registration_open',
            'olympiad_start_at' => now()->addDay(),
            'olympiad_end_at' => now()->addDay()->addHours(2),
        ], $attributes));
    }

    protected function olympiadAttempt(Olympiad $olympiad, User $user, array $attributes = []): OlympiadAttempt
    {
        $registration = OlympiadRegistration::forceCreate([
            'olympiad_id' => $olympiad->id, 'user_id' => $user->id, 'original_price' => 0, 'final_price' => 0, 'status' => 'confirmed',
        ]);

        return OlympiadAttempt::forceCreate(array_merge([
            'olympiad_id' => $olympiad->id, 'user_id' => $user->id, 'registration_id' => $registration->id,
            'session_token' => Str::random(64), 'total_max_score' => 100,
        ], $attributes));
    }
}
