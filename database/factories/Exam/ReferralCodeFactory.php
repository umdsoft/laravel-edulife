<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    protected $model = ReferralCode::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'code' => null, // generated on create
            'uses_count' => 0,
            'is_active' => true,
        ];
    }
}
