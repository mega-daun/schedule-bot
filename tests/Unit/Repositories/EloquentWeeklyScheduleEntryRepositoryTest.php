<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\WeeklyScheduleEntry;
use App\Repositories\EloquentWeeklyScheduleEntryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
beforeEach(function () {
    $this->repo = new EloquentWeeklyScheduleEntryRepository();
});

it('returns empty collection for empty input', function () {
    $result = $this->repo->createSchedule(Collection::make());

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toBeEmpty();
});

it('inserts all entries with single DB query', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 2, 'lesson_number' => 1],
    ]);

    DB::enableQueryLog();
    $this->repo->createSchedule($entries);
    $queryLog = DB::getQueryLog();
    DB::disableQueryLog();

    $insertQueries = array_filter($queryLog, fn ($q) => str_contains($q['query'], 'insert into'));
    expect($insertQueries)->toHaveCount(1);
});

it('returns collection of WeeklyScheduleEntry models', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
    ]);

    $result = $this->repo->createSchedule($entries);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(WeeklyScheduleEntry::class);
});

it('generates correct IDs for inserted entries', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 2, 'lesson_number' => 1],
    ]);

    $result = $this->repo->createSchedule($entries);

    expect($result->first()->id)->toBeInt()
        ->and($result->last()->id)->toBe($result->first()->id + 2);
});

it('preserves entry attributes in returned models', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 3, 'lesson_number' => 5],
    ]);

    $result = $this->repo->createSchedule($entries);

    expect($result[0]->class_id)->toBe($classroom->id)
        ->and($result[0]->subject_id)->toBe($subjects[0]->id)
        ->and($result[0]->weekday)->toBe(1)
        ->and($result[0]->lesson_number)->toBe(1)
        ->and($result[1]->class_id)->toBe($classroom->id)
        ->and($result[1]->subject_id)->toBe($subjects[1]->id)
        ->and($result[1]->weekday)->toBe(3)
        ->and($result[1]->lesson_number)->toBe(5);
});

it('marks returned models as existing', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subject->id, 'weekday' => 1, 'lesson_number' => 1],
    ]);

    $result = $this->repo->createSchedule($entries);

    expect($result->first()->exists)->toBeTrue();
});

it('persists entries to database', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
    ]);

    $this->repo->createSchedule($entries);

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
