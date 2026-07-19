<?php

declare(strict_types=1);

use App\DataObjects\Schedule\Lesson;
use App\DataObjects\Schedule\Weekday;
use App\Models\Subject;
use Illuminate\Support\Collection;

//
// Construction & fromLessons()
//

it('creates empty weekday with correct dayNumber', function () {
    $weekday = new Weekday(3);

    expect($weekday->getDayNumber())->toBe(3)
        ->and($weekday->getLessons())->toHaveCount(0)
        ->and($weekday->getUpstreamLessonNumber())->toBe(1);
});

it('creates weekday from lessons with re-indexed positions', function () {
    $lessons = collect([
        new Lesson(5, 99, 'Math', 1),
        new Lesson(5, 100, 'Physics', 2),
        new Lesson(5, 101, 'Chemistry', 3),
    ]);

    $weekday = Weekday::fromLessons(5, $lessons);

    expect($weekday->getDayNumber())->toBe(5)
        ->and($weekday->getLessons())->toHaveCount(3);

    $collected = $weekday->getLessons()->values();
    expect($collected[0]->getNumber())->toBe(1)
        ->and($collected[0]->getSubjectId())->toBe(1)
        ->and($collected[1]->getNumber())->toBe(2)
        ->and($collected[1]->getSubjectId())->toBe(2)
        ->and($collected[2]->getNumber())->toBe(3)
        ->and($collected[2]->getSubjectId())->toBe(3);
});

it('throws on non-Lesson item in collection', function () {
    Weekday::fromLessons(1, collect(['not a lesson']));
})->throws(InvalidArgumentException::class);

it('creates weekday from empty collection', function () {
    $weekday = Weekday::fromLessons(2, collect());

    expect($weekday->getDayNumber())->toBe(2)
        ->and($weekday->getLessons())->toHaveCount(0)
        ->and($weekday->getUpstreamLessonNumber())->toBe(1);
});

//
// addLesson()
//

it('assigns number 1 to first lesson', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(10, 'Math');

    $lessons = $weekday->getLessons();
    expect($lessons)->toHaveCount(1)
        ->and($lessons->first()->getNumber())->toBe(1)
        ->and($lessons->first()->getSubjectId())->toBe(10)
        ->and($lessons->first()->getSubjectName())->toBe('Math')
        ->and($lessons->first()->getWeekday())->toBe(1);
});

it('increments upstream lesson number after each add', function () {
    $weekday = new Weekday(1);

    expect($weekday->getUpstreamLessonNumber())->toBe(1);

    $weekday->addLesson(1, 'Math');
    expect($weekday->getUpstreamLessonNumber())->toBe(2);

    $weekday->addLesson(2, 'Physics');
    expect($weekday->getUpstreamLessonNumber())->toBe(3);

    $weekday->addLesson(3, 'Chemistry');
    expect($weekday->getUpstreamLessonNumber())->toBe(4);
});

it('appends multiple lessons sequentially', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');
    $weekday->addLesson(3, 'Chemistry');

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(3)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[2]->getNumber())->toBe(3);
});

it('allows duplicate subjects as separate lessons', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(1, 'Math');

    $lessons = $weekday->getLessons();
    expect($lessons)->toHaveCount(2);

    $values = $lessons->values();
    expect($values[0]->getNumber())->toBe(1)
        ->and($values[0]->getSubjectId())->toBe(1)
        ->and($values[1]->getNumber())->toBe(2)
        ->and($values[1]->getSubjectId())->toBe(1);
});

//
// removeLessonByNumber()
//

it('removes lesson at given position and re-indexes', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');
    $weekday->addLesson(3, 'Chemistry');

    $weekday->removeLessonByNumber(2);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(1)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[1]->getSubjectId())->toBe(3);
});

it('re-indexes remaining lessons after removal', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(2, 'B');
    $weekday->addLesson(3, 'C');
    $weekday->addLesson(4, 'D');

    $weekday->removeLessonByNumber(1);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(3)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[1]->getSubjectId())->toBe(3)
        ->and($lessons[2]->getNumber())->toBe(3)
        ->and($lessons[2]->getSubjectId())->toBe(4);
});

it('non-existent lesson number is a no-op', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $weekday->removeLessonByNumber(99);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[1]->getNumber())->toBe(2);
});

it('removing first lesson re-indexes correctly', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(2, 'B');
    $weekday->addLesson(3, 'C');

    $weekday->removeLessonByNumber(1);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[1]->getSubjectId())->toBe(3);
});

it('removing last lesson works', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(2, 'B');

    $weekday->removeLessonByNumber(2);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(1);
});

//
// removeLessonsBySubject()
//

it('removes by int subjectId', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $weekday->removeLessonsBySubject(1);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[0]->getNumber())->toBe(1);
});

it('removes by Subject model', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $subject = new Subject(['id' => 1, 'name' => 'Math']);
    $weekday->removeLessonsBySubject($subject);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[0]->getNumber())->toBe(1);
});

it('removes by string name', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $weekday->removeLessonsBySubject('Math');

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[0]->getNumber())->toBe(1);
});

it('no-op when subject not found', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $weekday->removeLessonsBySubject(999);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[1]->getNumber())->toBe(2);
});

it('removes all lessons with same subject and re-indexes', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(3, 'Chemistry');
    $weekday->addLesson(1, 'Math');

    $weekday->removeLessonsBySubject(1);

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[1]->getSubjectId())->toBe(3);
});

it('removes only matching lessons and preserves others', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(2, 'B');
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(3, 'C');

    $weekday->removeLessonsBySubject('A');

    $lessons = $weekday->getLessons()->values();
    expect($lessons)->toHaveCount(2)
        ->and($lessons[0]->getNumber())->toBe(1)
        ->and($lessons[0]->getSubjectId())->toBe(2)
        ->and($lessons[1]->getNumber())->toBe(2)
        ->and($lessons[1]->getSubjectId())->toBe(3);
});

//
// findLessonsBySubject()
//

it('finds lessons by int subjectId', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $found = $weekday->findLessonsBySubject(1);

    expect($found)->toBeInstanceOf(Collection::class)
        ->and($found)->toHaveCount(1)
        ->and($found->first()->getSubjectId())->toBe(1);
});

it('finds lessons by Subject model', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $subject = new Subject(['id' => 2, 'name' => 'Physics']);
    $found = $weekday->findLessonsBySubject($subject);

    expect($found)->toBeInstanceOf(Collection::class)
        ->and($found)->toHaveCount(1)
        ->and($found->first()->getSubjectId())->toBe(2);
});

it('finds lessons by string name', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $found = $weekday->findLessonsBySubject('Physics');

    expect($found)->toBeInstanceOf(Collection::class)
        ->and($found)->toHaveCount(1)
        ->and($found->first()->getSubjectName())->toBe('Physics');
});

it('returns all lessons when multiple share same subject', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(3, 'Chemistry');
    $weekday->addLesson(1, 'Math');

    $found = $weekday->findLessonsBySubject(1);

    expect($found)->toHaveCount(3);

    $ids = $found->map(fn (Lesson $l) => $l->getSubjectId())->values()->all();
    expect($ids)->toBe([1, 1, 1]);
});

it('returns empty collection when no match', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');
    $weekday->addLesson(2, 'Physics');

    $found = $weekday->findLessonsBySubject(999);

    expect($found)->toBeInstanceOf(Collection::class)
        ->and($found)->toHaveCount(0);
});

it('returns empty collection on empty weekday', function () {
    $weekday = new Weekday(1);

    $found = $weekday->findLessonsBySubject(1);

    expect($found)->toBeInstanceOf(Collection::class)
        ->and($found)->toHaveCount(0);
});

//
// hasLesson()
//

it('returns true when lesson exists by int', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');

    expect($weekday->hasLesson(1))->toBeTrue();
});

it('returns true when lesson exists by string', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');

    expect($weekday->hasLesson('Math'))->toBeTrue();
});

it('returns true when lesson exists by Subject model', function () {
    $weekday = new Weekday(1);
    $weekday->hasLesson(1);

    $weekday->addLesson(1, 'Math');
    $subject = new Subject(['id' => 1, 'name' => 'Math']);

    expect($weekday->hasLesson($subject))->toBeTrue();
});

it('returns false when no lesson matches', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'Math');

    expect($weekday->hasLesson(999))->toBeFalse()
        ->and($weekday->hasLesson('Physics'))->toBeFalse();
});

it('returns false on empty weekday', function () {
    $weekday = new Weekday(1);

    expect($weekday->hasLesson(1))->toBeFalse();
});

//
// Other
//

it('getDayNumber returns correct value', function () {
    expect((new Weekday(1))->getDayNumber())->toBe(1)
        ->and((new Weekday(7))->getDayNumber())->toBe(7);
});

it('getLessons returns all lessons', function () {
    $weekday = new Weekday(1);
    $weekday->addLesson(1, 'A');
    $weekday->addLesson(2, 'B');

    expect($weekday->getLessons())->toHaveCount(2);
});
