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
}
