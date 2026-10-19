<?php

namespace Database\Factories;

use App\Models\Direction;
use Illuminate\Database\Eloquent\Factories\Factory;

class DirectionFactory extends Factory
{
    protected $model = Direction::class;

    public function definition(): array
    {
        return [
            // directions table stores localized names (name_uz/ru/en)
            'name_uz' => $this->faker->randomElement(['Matematika', 'Fizika']),
            'name_ru' => $this->faker->randomElement(['Математика', 'Физика']),
            'name_en' => $this->faker->randomElement(['Mathematics', 'Physics']),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'icon' => '📐',
            'color' => $this->faker->hexColor(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
