<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WeeklyScheduleEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentWeeklyScheduleEntryRepository implements WeeklyScheduleEntryRepository
{
    public function createSchedule(Collection $entries): Collection
    {
        if ($entries->isEmpty()) {
            return Collection::make();
        }

        DB::table('weekly_schedule_entries')
            ->insert($entries->all());

        $lastId = (int) DB::connection()->getPdo()->lastInsertId();

        $startId = $lastId - $entries->count() + 1;

        $rows = $entries
            ->map(fn (array $entry, int $i): array => array_merge($entry, ['id' => $startId + $i]))
            ->toArray();

        return WeeklyScheduleEntry::query()->hydrate($rows);
    }
}
