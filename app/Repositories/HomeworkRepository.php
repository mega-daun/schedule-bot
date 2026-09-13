<?php

namespace App\Repositories;

use App\Models\Homework;
use Illuminate\Database\Eloquent\Collection;

interface HomeworkRepository
{
    public function createHomework(int $class_id, string $date, string $description, int $subject_id): bool;

    public function getHomeworks(int $class_id, string $start_date, string $end_date): Collection;

    public function findHomework(int $homework_id): ?Homework;

    public function deleteHomework(int $homework_id): bool;
}
