<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Mathematics',
                'Physics',
                'Chemistry',
                'Biology',
                'History',
                'Geography',
                'English',
                'Literature',
                'Computer Science',
                'Physical Education',
            ]),
        ];
    }
}
