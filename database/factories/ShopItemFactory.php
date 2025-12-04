<?php

namespace Database\Factories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopItemFactory extends Factory
{
    protected $model = ShopItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['avatar', 'badge', 'boost', 'hint', 'extra_life']),
            'category' => $this->faker->randomElement(['cosmetic', 'powerup', 'consumable']),
            'price' => $this->faker->randomElement([50, 100, 200, 500, 1000]),
            'icon' => '🎁',
            'image' => null,
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
            'min_level' => 1,
            'max_per_user' => $this->faker->randomElement([1, 5, 10, null]),
            'stock' => $this->faker->randomElement([null, 100, 500, 1000]),
            'duration_hours' => $this->faker->randomElement([null, 24, 168]),
            'metadata' => null,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
