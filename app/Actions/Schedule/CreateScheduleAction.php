<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\DataObjects\Schedule\Schedule;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\ScheduleRepository;

class CreateScheduleAction
{
    public function __construct(
        private ScheduleRepository $repository,
    ) {}

    public function __invoke(int $class_id, Schedule $schedule): bool
    {
        $classroom = Classroom::find($class_id);

        if ($classroom === null) {
            throw new InvalidInputException(__('error.class.not_member'));
        }

        $subjectIds = $schedule->getSubjects()->pluck('id')->unique()->toArray();

        $validCount = Subject::where('class_id', $class_id)
            ->whereIn('id', $subjectIds)
            ->count();

        if ($validCount !== count($subjectIds)) {
            throw new InvalidInputException(__('error.subject.not_found'));
        }

        $this->repository->purgeSchedule($class_id);

        return $this->repository->createSchedule($schedule, $class_id);
    }
}
