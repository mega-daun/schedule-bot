<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DataObjects\Schedule\Schedule;
use Illuminate\Support\Facades\DB;

class EloquentWeeklyScheduleEntryRepository implements WeeklyScheduleEntryRepository
{
    public function createSchedule(Schedule $schedule, int $class_id): bool
    {
        $entries = collect();
        foreach ($schedule->getWeekdays() as $weekday) {
            foreach ($weekday->getLessons() as $lesson) {
                $entries->add(['subject_id' => $lesson->getSubjectId(), 'lesson_number' => $lesson->getNumber(), 'weekday' => $lesson->getWeekday(), 'class_id' => $class_id]);
            }
        }

        return DB::table('weekly_schedule_entries')
            ->insert($entries->all());
    }
}
