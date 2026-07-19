<?php

declare(strict_types=1);

use App\Actions\Schedule\CreateScheduleAction;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\WeeklyScheduleEntryRepository;
use Illuminate\Support\Collection;

afterEach(function () {
    Mockery::close();
});

it('rejects non-existent class', function () {
    $repository = Mockery::mock(WeeklyScheduleEntryRepository::class);

    $entries = Collection::make([
        ['class_id' => 999, 'subject_id' => 1, 'weekday' => 1, 'lesson_number' => 1],
    ]);

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: 999, entries: $entries);
})->throws(InvalidInputException::class);

it('rejects subject not belonging to class', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $otherClassroom->id]);

    $repository = Mockery::mock(WeeklyScheduleEntryRepository::class);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subject->id, 'weekday' => 1, 'lesson_number' => 1],
    ]);

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, entries: $entries);
})->throws(InvalidInputException::class);

it('rejects non-existent subject', function () {
    $classroom = Classroom::factory()->create();

    $repository = Mockery::mock(WeeklyScheduleEntryRepository::class);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => 99999, 'weekday' => 1, 'lesson_number' => 1],
    ]);

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, entries: $entries);
})->throws(InvalidInputException::class);

it('calls repository and returns result on success', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
    ]);

    $expectedResult = Collection::make([
        ['id' => 1, 'class_id' => $classroom->id, 'subject_id' => $subjects[0]->id, 'weekday' => 1, 'lesson_number' => 1],
        ['id' => 2, 'class_id' => $classroom->id, 'subject_id' => $subjects[1]->id, 'weekday' => 1, 'lesson_number' => 2],
    ]);

    $repository = Mockery::mock(WeeklyScheduleEntryRepository::class);
    $repository->shouldReceive('createSchedule')
        ->once()
        ->with($entries)
        ->andReturn($expectedResult);

    $action = new CreateScheduleAction(repository: $repository);
    $result = $action(class_id: $classroom->id, entries: $entries);

    expect($result)->toBe($expectedResult);
});

it('validates multiple subjects with single query', function () {
    $classroom = Classroom::factory()->create();
    $validSubject = Subject::factory()->create(['class_id' => $classroom->id]);

    $repository = Mockery::mock(WeeklyScheduleEntryRepository::class);

    $entries = Collection::make([
        ['class_id' => $classroom->id, 'subject_id' => $validSubject->id, 'weekday' => 1, 'lesson_number' => 1],
        ['class_id' => $classroom->id, 'subject_id' => 99999, 'weekday' => 1, 'lesson_number' => 2],
    ]);

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, entries: $entries);
})->throws(InvalidInputException::class);
