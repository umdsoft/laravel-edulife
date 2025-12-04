<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Models\Direction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        
        return [
            'teacher_id' => User::factory(),
            'direction_id' => Direction::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(),
            'thumbnail' => null,
            'preview_video' => null,
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => 'uz',
            'price' => $this->faker->randomElement([0, 50000, 100000, 200000, 500000]),
            'discount_price' => null,
            'is_free' => $this->faker->boolean(30),
            'status' => 'published',
            'is_featured' => $this->faker->boolean(20),
            'duration_hours' => $this->faker->numberBetween(5, 50),
            'lessons_count' => $this->faker->numberBetween(10, 100),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }
}
