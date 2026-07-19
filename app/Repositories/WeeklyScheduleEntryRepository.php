<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;

interface WeeklyScheduleEntryRepository
{
    /**
     * @param  Collection<int, array{subject_id: int, weekday: int, lesson_number: int}>  $entries
     * @return Collection<int, array{id: int, subject_id: int, weekday: int, lesson_number: int}>
     */
    public function createSchedule(Collection $entries): Collection;
}
