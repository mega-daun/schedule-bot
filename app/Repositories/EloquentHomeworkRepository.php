<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Homework;
use Illuminate\Database\Eloquent\Collection;

class EloquentHomeworkRepository implements HomeworkRepository
{
    public function createHomework(int $class_id, string $date, string $description, int $subject_id): bool
    {
        return (bool) Homework::create([
            'class_id' => $class_id,
            'date' => $date,
            'description' => $description,
            'subject_id' => $subject_id,
        ]);
    }

    public function getHomeworks(int $class_id, string $start_date, string $end_date): Collection
    {
        return Homework::where('class_id', $class_id)
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date')
            ->get();
    }

    public function findHomework(int $homework_id): ?Homework
    {
        return Homework::find($homework_id);
    }

    public function deleteHomework(int $homework_id): bool
    {
        return (bool) Homework::destroy($homework_id);
    }
}
