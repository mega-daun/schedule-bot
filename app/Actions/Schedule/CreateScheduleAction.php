<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\WeeklyScheduleEntryRepository;
use Illuminate\Support\Collection;

class CreateScheduleAction
{
    public function __construct(
        private WeeklyScheduleEntryRepository $repository,
    ) {}

    public function __invoke(int $class_id, Collection $entries): Collection
    {
        $classroom = Classroom::find($class_id);

        if ($classroom === null) {
            throw new InvalidInputException(__('error.class.not_member'));
        }

        $subjectIds = $entries->pluck('subject_id')->unique()->values()->toArray();

        $validCount = Subject::where('class_id', $class_id)
            ->whereIn('id', $subjectIds)
            ->count();

        if ($validCount !== count($subjectIds)) {
            throw new InvalidInputException(__('error.subject.not_found'));
        }

        return $this->repository->createSchedule($entries);
    }
}
