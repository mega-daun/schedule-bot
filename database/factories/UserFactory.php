<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => 123456789,
            'first_name' => 'Саша',
            'username' => 'YoppaniySir',
            'language_code' => 'ru',
            'role' => UserRole::Student,
            'class_id' => null,
        ];
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Student,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Teacher,
        ]);
    }

    public function onDuty(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::OnDuty,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function withClass(ClassroomFactory $classroom): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $classroom,
        ]);
    }
}
