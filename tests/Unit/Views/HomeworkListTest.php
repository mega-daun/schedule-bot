<?php

use App\DataObjects\Schedule\Schedule;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Telegram\Messages\HomeworkList;

use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

class TestableHomeworkList
{
    use HomeworkList;
}

it('should output homework for stated date', function () {
    $printer = new TestableHomeworkList;
    $c = Classroom::factory()->create();
    $s = Subject::factory()->for($c)->create();
    $homework = Homework::factory()
        ->create([
            'class_id' => $c->id,
            'subject_id' => $s->id,
            'date' => now(),
        ]);
    $schedule = new Schedule([now()->isoWeekday()]);
    $schedule->addLesson(now()->isoWeekday(), $s->id, $s->name);
    $output = $printer->makeHomeworkListOnDay(now(), $schedule, collect([$homework]));
    assertStringContainsString($homework->description, $output, 'Daily view does not print homework description with corresponding date');
    assertStringNotContainsString(__('info.homework.no_homework'), $output, 'Daily view prints no homework when homework is present');
    $output = $printer->makeHomeworkListOnWeek(now(), $schedule, collect([$homework]));
    assertStringContainsString($homework->description, $output, 'Weekly view does not print homework description with corresponding date');
    assertStringNotContainsString(__('info.homework.no_homework'), $output, 'Weekly view prints no homework when homework is present');
});

it('should not output homework for unstated date', function () {
    $printer = new TestableHomeworkList;
    $c = Classroom::factory()->create();
    $s = Subject::factory()->for($c)->create();
    $homework = Homework::factory()
        ->create([
            'class_id' => $c->id,
            'subject_id' => $s->id,
            'date' => now(),
        ]);
    $schedule = new Schedule;
    $schedule->addLesson(now()->isoWeekday(), $s->id, $s->name);
    $schedule->addLesson(now()->isoWeekday() + 1, $s->id, $s->name);
    $output = $printer->makeHomeworkListOnDay(now()->addDay(), $schedule, collect([$homework]));
    assertStringContainsString(__('info.homework.no_homework'), $output, 'Daily view does not print no homework message when there is no homework');
    assertStringNotContainsString($homework->description, $output, 'Daily view does print homework description with wrong date');
    $output = $printer->makeHomeworkListOnWeek(now()->addWeek(), $schedule, collect([$homework]));
    assertStringNotContainsString($homework->description, $output, 'Weekly view does print homework description with wrong date');
    assertStringContainsString(__('info.homework.no_homework'), $output, 'Weekly view does not print no homework message when there is no homework');
});

it('should output headers only for work days', function () {
    $printer = new TestableHomeworkList;
    $schedule = new Schedule(range(1, 5));
    $output = $printer->makeHomeworkListOnWeek(now(), $schedule, collect([]));
    assertStringContainsString(__('general.weekday.1'), $output, 'Weekly view does not print monday header when it is a work day');
    assertStringContainsString(__('general.weekday.2'), $output, 'Weekly view does not print weekday header when it is a work day');
    assertStringContainsString(__('general.weekday.3'), $output, 'Weekly view does not print weekday header when it is a work day');
    assertStringContainsString(__('general.weekday.4'), $output, 'Weekly view does not print weekday header when it is a work day');
    assertStringContainsString(__('general.weekday.5'), $output, 'Weekly view does not print weekday header when it is a work day');
    assertStringNotContainsString(__('general.weekday.6'), $output, 'Weekly view does print sunday header when it is not a work day');
    assertStringNotContainsString(__('general.weekday.7'), $output, 'Weekly view does print saturday header when it is not a work day');
});

it('should leave \'no homework\' message when day is not a work day', function () {
    $printer = new TestableHomeworkList;
    $c = Classroom::factory()->create();
    $s = Subject::factory()->for($c)->create();
    $homework = Homework::factory()
        ->create([
            'class_id' => $c->id,
            'subject_id' => $s->id,
            'date' => now(),
        ]);
    $schedule = new Schedule([now()->addDay()->isoWeekday()]);
    $schedule->addLesson(now()->addDay()->isoWeekday(), $s->id, $s->name);
    $output = $printer->makeHomeworkListOnDay(now(), $schedule, collect([$homework]));
    assertStringContainsString(__('info.homework.no_homework'), $output, 'View does not print no homeworks message on chill day');
    assertStringNotContainsString($homework->description, $output, 'View does print homework for a chill day');
});

test('edge case: same subject on different dates with one homework', function () {
    $printer = new TestableHomeworkList;
    $c = Classroom::factory()->create();
    $s = Subject::factory()->for($c)->create();
    $homework = Homework::factory()
        ->create([
            'class_id' => $c->id,
            'subject_id' => $s->id,
            'date' => now(),
        ]);
    $schedule = new Schedule([now()->isoWeekday(), now()->addDay()->isoWeekday()]);
    $schedule->addLesson(now()->isoWeekday(), $s->id, $s->name);
    $schedule->addLesson(now()->addDay()->isoWeekday(), $s->id, $s->name);
    $output = $printer->makeHomeworkListOnDay(now(), $schedule, collect([$homework]));
    assertStringContainsString($homework->description, $output, 'View does not print homework for correct day');
    $output = $printer->makeHomeworkListOnDay(now()->addDay(), $schedule, collect([$homework]));
    assertStringContainsString(__('info.homework.no_homework'), $output, 'View prints homework for incorrect day');
    $output = $printer->makeHomeworkListOnWeek(now(), $schedule, collect([$homework]));
    assertStringContainsString($homework->description, $output, 'View not prints homework for correct day');
    assertStringContainsString(__('info.homework.no_homework'), $output, 'View not prints no homework message for incorrect day');
});
