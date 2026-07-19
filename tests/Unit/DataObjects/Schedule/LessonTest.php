<?php

declare(strict_types=1);

use App\DataObjects\Schedule\Lesson;

it('stores and returns all values correctly', function () {
    $lesson = new Lesson(weekday: 3, number: 2, subjectName: 'Math', subjectId: 42);

    expect($lesson->getWeekday())->toBe(3)
        ->and($lesson->getNumber())->toBe(2)
        ->and($lesson->getSubjectName())->toBe('Math')
        ->and($lesson->getSubjectId())->toBe(42);
});

it('returns correct values for first lesson of monday', function () {
    $lesson = new Lesson(1, 1, 'Physics', 10);

    expect($lesson->getWeekday())->toBe(1)
        ->and($lesson->getNumber())->toBe(1)
        ->and($lesson->getSubjectName())->toBe('Physics')
        ->and($lesson->getSubjectId())->toBe(10);
});

it('returns correct values for last lesson of sunday', function () {
    $lesson = new Lesson(7, 8, 'Art', 99);

    expect($lesson->getWeekday())->toBe(7)
        ->and($lesson->getNumber())->toBe(8)
        ->and($lesson->getSubjectName())->toBe('Art')
        ->and($lesson->getSubjectId())->toBe(99);
});
