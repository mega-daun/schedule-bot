<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'weekday' => 1,
            'lesson_number' => 1,
        ];
    }
}
