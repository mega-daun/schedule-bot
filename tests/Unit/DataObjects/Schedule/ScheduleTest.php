<?php

declare(strict_types=1);

use App\DataObjects\Schedule\Lesson;
use App\DataObjects\Schedule\Schedule;
use App\DataObjects\Schedule\Weekday;
use App\Models\Subject;
use Illuminate\Support\Collection;

//
// Construction
//

it('creates 7 weekdays by default', function () {
    $schedule = new Schedule();

    $weekdays = $schedule->getWeekdays();
    expect($weekdays)->toHaveCount(7);

    for ($i = 1; $i <= 7; $i++) {
        expect($weekdays->has($i))->toBeTrue()
            ->and($weekdays->get($i))->toBeInstanceOf(Weekday::class)
            ->and($weekdays->get($i)->getDayNumber())->toBe($i);
    }
});

it('creates schedule with custom workDays', function () {
    $schedule = new Schedule([1, 2, 3, 4, 5]);

    $weekdays = $schedule->getWeekdays();
    expect($weekdays)->toHaveCount(5);

    for ($i = 1; $i <= 5; $i++) {
        expect($weekdays->has($i))->toBeTrue();
    }
});

it('rejects day number greater than 7', function () {
    new Schedule([1, 2, 8]);
})->throws(InvalidArgumentException::class);

it('rejects day number less than 1', function () {
    new Schedule([0, 1, 2]);
})->throws(InvalidArgumentException::class);

it('rejects non-int in workDays', function () {
    new Schedule([1, 'monday', 3]);
})->throws(InvalidArgumentException::class);

it('allows duplicate day numbers (second overwrites)', function () {
    $schedule = new Schedule([1, 1, 2]);

    expect($schedule->getWeekdays())->toHaveCount(2);
});

//
// fromWeekdays()
//

it('builds schedule from pre-populated weekday collection', function () {
    $monday = new Weekday(1);
    $monday->addLesson(1, 'Math');

    $friday = new Weekday(5);
    $friday->addLesson(2, 'Physics');

    $schedule = Schedule::fromWeekdays(collect([$monday, $friday]));

    expect($schedule->getWeekdays())->toHaveCount(2)
        ->and($schedule->getWeekday(1)->getLessons())->toHaveCount(1)
        ->and($schedule->getWeekday(5)->getLessons())->toHaveCount(1);
});

it('throws on non-Weekday in collection', function () {
    Schedule::fromWeekdays(collect(['not a weekday']));
})->throws(Error::class);

it('throws on duplicate weekday day number in collection', function () {
    $monday1 = new Weekday(1);
    $monday2 = new Weekday(1);

    Schedule::fromWeekdays(collect([$monday1, $monday2]));
})->throws(InvalidArgumentException::class);

//
// getWeekday()
//

it('returns correct weekday', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');

    $weekday = $schedule->getWeekday(1);
    expect($weekday)->toBeInstanceOf(Weekday::class)
        ->and($weekday->getDayNumber())->toBe(1)
        ->and($weekday->getLessons())->toHaveCount(1);
});

it('throws for day 0', function () {
    (new Schedule())->getWeekday(0);
})->throws(InvalidArgumentException::class);

it('throws for day 8', function () {
    (new Schedule())->getWeekday(8);
})->throws(InvalidArgumentException::class);

it('throws for non-work-day', function () {
    $schedule = new Schedule([1, 2, 3, 4, 5]);
    $schedule->getWeekday(6);
})->throws(InvalidArgumentException::class);

//
// Proxy methods
//

it('addLesson delegates to correct weekday', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');

    $lessons = $schedule->getLessons(1);
    expect($lessons)->toHaveCount(1)
        ->and($lessons->first()->getSubjectId())->toBe(10)
        ->and($lessons->first()->getSubjectName())->toBe('Math')
        ->and($lessons->first()->getWeekday())->toBe(1);
});

it('addLesson throws for invalid weekday', function () {
    (new Schedule())->addLesson(8, 10, 'Math');
})->throws(InvalidArgumentException::class);

it('removeLessonsBySubject delegates correctly', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');
    $schedule->addLesson(1, 20, 'Physics');

    $schedule->removeLessonsBySubject(1, 10);

    $lessons = $schedule->getLessons(1)->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getSubjectId())->toBe(20);
});

it('removeLessonByNumber delegates correctly', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');
    $schedule->addLesson(1, 20, 'Physics');

    $schedule->removeLessonByNumber(1, 1);

    $lessons = $schedule->getLessons(1)->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getSubjectId())->toBe(20)
        ->and($lessons[0]->getNumber())->toBe(1);
});

it('hasLesson delegates correctly', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');

    expect($schedule->hasLesson(1, 10))->toBeTrue()
        ->and($schedule->hasLesson(1, 999))->toBeFalse();
});

it('findLessonsBySubject delegates correctly', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');
    $schedule->addLesson(1, 20, 'Physics');

    $found = $schedule->findLessonsBySubject(1, 10);
    expect($found)->toHaveCount(1)
        ->and($found->first()->getSubjectId())->toBe(10);
});

it('getLessons returns lessons from correct weekday', function () {
    $schedule = new Schedule();
    $schedule->addLesson(1, 10, 'Math');
    $schedule->addLesson(3, 20, 'Physics');

    expect($schedule->getLessons(1))->toHaveCount(1)
        ->and($schedule->getLessons(3))->toHaveCount(1)
        ->and($schedule->getLessons(2))->toHaveCount(0);
});

//
// getWeekdays()
//

it('returns collection of all weekdays', function () {
    $schedule = new Schedule();
    $weekdays = $schedule->getWeekdays();

    expect($weekdays)->toBeInstanceOf(Collection::class)
        ->and($weekdays)->toHaveCount(7);
});
