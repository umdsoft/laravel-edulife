<?php

namespace Database\Factories\Exam;

use App\Models\Exam\Referral;
use App\Models\Exam\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'referral_code_id' => ReferralCode::factory(),
            'referrer_id' => fn (array $attrs) => ReferralCode::find($attrs['referral_code_id'])?->owner_id,
            'referred_id' => User::factory(),
            'status' => 'joined',
            'joined_at' => now(),
        ];
    }
}
