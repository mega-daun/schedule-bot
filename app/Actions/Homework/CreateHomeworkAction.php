<?php

namespace App\Actions\Homework;

use App\Repositories\HomeworkRepository;
use Exception;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CreateHomeworkAction
{
    public function __construct(private HomeworkRepository $homeworkRepository) {}

    public function __invoke(int $class_id, int $subject_id, Carbon $date, string $description)
    {
        $description = trim($description);

        if ($description == '') {
            throw new InvalidArgumentException;
        }

        if (! $this->homeworkRepository->createHomework(
            $class_id,
            $date->format('Y-m-d'),
            $description,
            $subject_id
        )) {
            throw new Exception;
        }
    }
}
