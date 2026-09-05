<?php

namespace App\Repositories;

interface HomeworkRepository
{
    public function createHomework(int $class_id, string $date, string $description, int $subject_id): bool;
}
