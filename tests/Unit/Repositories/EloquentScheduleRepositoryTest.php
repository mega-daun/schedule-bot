<?php

declare(strict_types=1);

use App\DataObjects\Schedule\Lesson;
use App\DataObjects\Schedule\Schedule;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\WeeklyScheduleEntry;
use App\Repositories\EloquentScheduleRepository;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;

beforeEach(function () {
    $this->repo = new EloquentScheduleRepository;
});

it('returns true for empty schedule', function () {
    $classroom = Classroom::factory()->create();
    $schedule = new Schedule;

    $result = $this->repo->createSchedule($schedule, $classroom->id);

    expect($result)->toBeTrue();
});

it('returns true on successful insert', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $schedule = new Schedule;
    $schedule->addLesson(1, $subject->id, $subject->name);

    $result = $this->repo->createSchedule($schedule, $classroom->id);

    expect($result)->toBeTrue();
});

it('inserts all entries with single DB query', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $schedule = new Schedule;
    $schedule->addLesson(1, $subjects[0]->id, $subjects[0]->name);
    $schedule->addLesson(1, $subjects[1]->id, $subjects[1]->name);
    $schedule->addLesson(2, $subjects[0]->id, $subjects[0]->name);

    DB::enableQueryLog();
    $this->repo->createSchedule($schedule, $classroom->id);
    $queryLog = DB::getQueryLog();
    DB::disableQueryLog();

    $insertQueries = array_filter($queryLog, fn ($q) => str_contains($q['query'], 'insert into'));
    expect($insertQueries)->toHaveCount(1);
});

it('persists entries to database', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $schedule = new Schedule;
    $schedule->addLesson(1, $subjects[0]->id, $subjects[0]->name);
    $schedule->addLesson(1, $subjects[1]->id, $subjects[1]->name);

    $this->repo->createSchedule($schedule, $classroom->id);

    $this->assertDatabaseCount('weekly_schedule_entries', 2);
    $this->assertDatabaseHas('weekly_schedule_entries', [
        'class_id' => $classroom->id,
        'subject_id' => $subjects[0]->id,
        'weekday' => 1,
        'lesson_number' => 1,
    ]);
    $this->assertDatabaseHas('weekly_schedule_entries', [
        'class_id' => $classroom->id,
        'subject_id' => $subjects[1]->id,
        'weekday' => 1,
        'lesson_number' => 2,
    ]);
});

it('gets correct schedule', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);
    WeeklyScheduleEntry::factory(3)->state(
        new Sequence(
            ['weekday' => 1],
            ['weekday' => 2],
            ['weekday' => 3]
        )
    )->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);
    $schedule = $this->repo->getSchedule($classroom->id);
    assertContains([1, 2, 3], $schedule->getWorkdays());
    assertEquals([$subject->name, $subject->name, $subject->name], $schedule->getLessons()->map(fn (Lesson $l) => $l->getSubjectName()));
});

it('returns empty Schedule object when there is no records');
it('creates and gets the same schedule', function () {});

it('returns workdays correctly', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);
    WeeklyScheduleEntry::factory(3)->state(
        new Sequence(
            ['weekday' => 1],
            ['weekday' => 2],
            ['weekday' => 3]
        )
    )->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);

    $workDays = $this->repo->getWorkDays($classroom->id);
    assertContains([1, 2, 3], $workDays);
});
