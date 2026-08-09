<?php

declare(strict_types=1);

use App\Actions\Schedule\CreateScheduleAction;
use App\DataObjects\Schedule\Schedule;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\ScheduleRepository;

afterEach(function () {
    Mockery::close();
});

it('rejects non-existent class', function () {
    $repository = Mockery::mock(ScheduleRepository::class);

    $schedule = new Schedule;

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: 999, schedule: $schedule);
})->throws(InvalidInputException::class);

it('rejects subject not belonging to class', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $otherClassroom->id]);

    $repository = Mockery::mock(ScheduleRepository::class);

    $schedule = new Schedule;
    $schedule->addLesson(1, $subject->id, $subject->name);

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, schedule: $schedule);
})->throws(InvalidInputException::class);

it('rejects non-existent subject', function () {
    $classroom = Classroom::factory()->create();

    $repository = Mockery::mock(ScheduleRepository::class);

    $schedule = new Schedule;
    $schedule->addLesson(1, 99999, 'Non-existent Subject');

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, schedule: $schedule);
})->throws(InvalidInputException::class);

it('calls repository and returns result on success', function () {
    $classroom = Classroom::factory()->create();
    $subjects = Subject::factory()->count(2)->create(['class_id' => $classroom->id]);

    $schedule = new Schedule;
    $schedule->addLesson(1, $subjects[0]->id, $subjects[0]->name);
    $schedule->addLesson(1, $subjects[1]->id, $subjects[1]->name);

    $repository = Mockery::mock(ScheduleRepository::class);
    $repository->shouldReceive('createSchedule')
        ->once()
        ->with($schedule, $classroom->id)
        ->andReturn(true);
    $repository->shouldReceive('purgeSchedule')->once()->with($classroom->id)->andReturn(true);

    $action = new CreateScheduleAction(repository: $repository);
    $result = $action(class_id: $classroom->id, schedule: $schedule);

    expect($result)->toBeTrue();
});

it('validates multiple subjects with single query', function () {
    $classroom = Classroom::factory()->create();
    $validSubject = Subject::factory()->create(['class_id' => $classroom->id]);

    $repository = Mockery::mock(ScheduleRepository::class);

    $schedule = new Schedule;
    $schedule->addLesson(1, $validSubject->id, $validSubject->name);
    $schedule->addLesson(1, 99999, 'Non-existent Subject');

    $action = new CreateScheduleAction(repository: $repository);
    $action(class_id: $classroom->id, schedule: $schedule);
})->throws(InvalidInputException::class);
