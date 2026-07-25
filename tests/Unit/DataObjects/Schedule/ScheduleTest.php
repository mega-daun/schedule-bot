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

//
// toJson()
//

it('serializes empty schedule to JSON with named keys', function () {
    $schedule = new Schedule();
    $json = $schedule->toJson();
    $data = json_decode($json, true);

    expect($data)->toHaveKeys(['lessons', 'work_days'])
        ->and($data['lessons'])->toBe([])
        ->and($data['work_days'])->toBe([1, 2, 3, 4, 5, 6, 7]);
});

it('serializes lessons into JSON', function () {
    $schedule = new Schedule([1, 2, 3, 4, 5]);
    $schedule->addLesson(1, 10, 'Math');
    $schedule->addLesson(1, 20, 'Physics');
    $schedule->addLesson(3, 30, 'History');

    $data = json_decode($schedule->toJson(), true);

    expect($data['lessons'])->toHaveCount(3)
        ->and($data['work_days'])->toBe([1, 2, 3, 4, 5]);
});

it('serializes custom work_days', function () {
    $schedule = new Schedule([1, 3, 5]);
    $data = json_decode($schedule->toJson(), true);

    expect($data['work_days'])->toBe([1, 3, 5]);
});

it('passes options to json_encode', function () {
    $schedule = new Schedule();
    $json = $schedule->toJson(JSON_PRETTY_PRINT);

    expect($json)->toContain("\n");
});

//
// fromJson()
//

it('reconstructs empty schedule from JSON', function () {
    $json = (new Schedule())->toJson();
    $schedule = Schedule::fromJson($json);

    expect($schedule->getWeekdays())->toHaveCount(7)
        ->and($schedule->getLessons())->toHaveCount(0);
});

it('round-trips schedule with lessons through toJson/fromJson', function () {
    $original = new Schedule([1, 2, 3, 4, 5]);
    $original->addLesson(1, 10, 'Math');
    $original->addLesson(1, 20, 'Physics');
    $original->addLesson(3, 30, 'History');
    $original->addLesson(5, 40, 'English');

    $restored = Schedule::fromJson($original->toJson());

    expect($restored->getWeekdays())->toHaveCount(5)
        ->and($restored->getLessons(1))->toHaveCount(2)
        ->and($restored->getLessons(3))->toHaveCount(1)
        ->and($restored->getLessons(5))->toHaveCount(1);

    $monday = $restored->getLessons(1)->values();
    expect($monday[0]->getSubjectId())->toBe(10)
        ->and($monday[0]->getSubjectName())->toBe('Math')
        ->and($monday[1]->getSubjectId())->toBe(20)
        ->and($monday[1]->getSubjectName())->toBe('Physics');
});

it('round-trips custom work_days through toJson/fromJson', function () {
    $original = new Schedule([1, 3, 5]);
    $original->addLesson(1, 10, 'Math');

    $restored = Schedule::fromJson($original->toJson());

    expect($restored->getWeekdays())->toHaveCount(3)
        ->and($restored->getWeekday(1))->not->toBeNull()
        ->and($restored->getWeekday(3))->not->toBeNull()
        ->and($restored->getWeekday(5))->not->toBeNull();
});

it('round-trips schedule preserving lesson order within weekday', function () {
    $original = new Schedule();
    $original->addLesson(1, 10, 'First');
    $original->addLesson(1, 20, 'Second');
    $original->addLesson(1, 30, 'Third');

    $restored = Schedule::fromJson($original->toJson());
    $lessons = $restored->getLessons(1)->values();

    expect($lessons[0]->getSubjectId())->toBe(10)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[1]->getSubjectId())->toBe(20)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[2]->getSubjectId())->toBe(30)
        ->and($lessons[2]->getNumber())->toBe(3);
});

//
// fromJson() — error cases
//

it('throws JsonException on invalid JSON string', function () {
    Schedule::fromJson('{broken json');
})->throws(JsonException::class);

it('throws JsonException when lessons key is missing', function () {
    $json = json_encode(['work_days' => [1, 2, 3, 4, 5, 6, 7]]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when work_days key is missing', function () {
    $json = json_encode(['lessons' => []]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when lessons is not an array', function () {
    $json = json_encode(['lessons' => 'not an array', 'work_days' => [1, 2, 3, 4, 5, 6, 7]]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when work_days is not an array', function () {
    $json = json_encode(['lessons' => [], 'work_days' => 'not an array']);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when work_days contain invalid day', function () {
    $json = json_encode(['lessons' => [], 'work_days' => [1, 8]]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when lesson has missing fields', function () {
    $json = json_encode([
        'lessons' => [['weekday' => 1]],
        'work_days' => [1, 2, 3, 4, 5, 6, 7],
    ]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when lesson weekday is not a work day', function () {
    $json = json_encode([
        'lessons' => [[
            'subject_id' => 10,
            'subject_name' => 'Math',
            'weekday' => 6,
            'lesson_number' => 1,
        ]],
        'work_days' => [1, 2, 3, 4, 5],
    ]);
    Schedule::fromJson($json);
})->throws(JsonException::class);

it('throws JsonException when lesson subject_id is not int', function () {
    $json = json_encode([
        'lessons' => [[
            'subject_id' => 'ten',
            'subject_name' => 'Math',
            'weekday' => 1,
            'lesson_number' => 1,
        ]],
        'work_days' => [1, 2, 3, 4, 5, 6, 7],
    ]);
    Schedule::fromJson($json);
})->throws(JsonException::class);
