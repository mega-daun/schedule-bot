<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'weekday' => $this->faker->numberBetween(1, 7),
            'lesson_number' => $this->faker->numberBetween(1, 8),
        ];
    }
}
