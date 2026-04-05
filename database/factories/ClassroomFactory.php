<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('??###')),
            'join_token' => Classroom::generateJoinToken(),
        ];
    }
}
