<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DataObjects\Schedule\Schedule;

interface WeeklyScheduleEntryRepository
{
    /**
     * @return Schedule $schedule
     */
    public function createSchedule(Schedule $schedule, int $class_id): bool;
}
