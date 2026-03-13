<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HomeworkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'description' => $this->faker->paragraph(),
        ];
    }

    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->toDateString(),
        ]);
    }

    public function forTomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->addDay()->toDateString(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-1 week', 'yesterday')->toDateString(),
        ]);
    }
}
