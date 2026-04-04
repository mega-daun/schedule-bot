<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'weekday' => fake()->unique()->numberBetween(1, 7),
            'lesson_number' => fake()->unique()->numberBetween(1, 8),
        ];
    }

    public function monday(): static
    {
        return $this->state(fn (array $attributes) => [
            'weekday' => 1,
        ]);
    }

    public function firstLesson(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_number' => 1,
        ]);
    }
}
