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
            'name' => $this->faker->randomElement([
                'Web Development',
                'Mobile Development',
                'Data Science',
                'Machine Learning',
                'UI/UX Design',
                'DevOps',
                'Cybersecurity',
            ]),
            'slug' => $this->faker->slug(2),
            'description' => $this->faker->sentence(),
            'icon' => '💻',
            'color' => $this->faker->hexColor(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
