<?php

namespace Database\Factories\Exam;

use App\Models\Exam\TelegramAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramAccount>
 */
class TelegramAccountFactory extends Factory
{
    protected $model = TelegramAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegram_id' => fake()->unique()->numberBetween(100000000, 9999999999),
            'chat_id' => fn (array $attrs) => $attrs['telegram_id'],
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'language_code' => 'uz',
            'is_blocked' => false,
            'linked_at' => now(),
        ];
    }

    public function unlinked(): static
    {
        return $this->state(['user_id' => null, 'linked_at' => null]);
    }
}
