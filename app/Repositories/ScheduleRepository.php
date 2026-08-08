<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DataObjects\Schedule\Schedule;

interface ScheduleRepository
{
    public function createSchedule(Schedule $schedule, int $class_id): bool;

    public function purgeSchedule(int $class_id): bool;
}
