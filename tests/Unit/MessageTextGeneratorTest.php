<?php

use App\Helpers\MessageTextGenerator;
use Illuminate\Support\Collection;

function makeHomework(string $date, string $description): object
{
    return (object)['date' => $date, 'description' => $description];
}

function makeHomeworks(array $items): Collection
{
    return Collection::make($items)->map(
        fn($item) => makeHomework($item['date'], $item['description'])
    );
}

it('generates header for single day', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-07-01');
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $start);

    expect($result)->toStartWith("❗️ДЗ на 01.07\n");
});

it('generates header for date range', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29');
    $end = new DateTime('2026-07-04');
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $end);

    expect($result)->toStartWith("❗️ДЗ на 29.06-04.07\n");
});

it('renders day entry with correct format', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-30'); // Tuesday
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $start);

    expect($result)->toContain("⬜️ Вторник(30.06)\n");
});

it('renders homework item with bullet', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-07-01');
    $homeworks = makeHomeworks([
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
    ]);

    $result = $generator->homeworkView($homeworks, $start, $start);

    expect($result)->toContain("▫️ Русский язык: задание 1\n");
});

it('renders multiple homeworks on same day', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-07-01');
    $homeworks = makeHomeworks([
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-01', 'description' => 'Математика: задание 2'],
    ]);

    $result = $generator->homeworkView($homeworks, $start, $start);

    expect($result)->toContain("▫️ Русский язык: задание 1\n");
    expect($result)->toContain("▫️ Математика: задание 2\n");
});

it('renders no-homework message when day has no homeworks', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-07-01');
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $start);

    expect($result)->toContain("▫️ Ничего не задали :)\n");
});

it('groups homeworks under correct day in multi-day range', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29'); // Monday
    $end = new DateTime('2026-07-01');   // Wednesday
    $homeworks = makeHomeworks([
        ['date' => '2026-07-01', 'description' => 'Среда задание'],
        ['date' => '2026-06-29', 'description' => 'Понедельник задание'],
    ]);

    $result = $generator->homeworkView($homeworks, $start, $end);

    $mondayPos = strpos($result, 'Понедельник');
    $wednesdayPos = strpos($result, 'Среда');
    $mondayHomeworkPos = strpos($result, 'Понедельник задание');
    $wednesdayHomeworkPos = strpos($result, 'Среда задание');

    expect($mondayPos)->toBeLessThan($wednesdayPos);
    expect($mondayHomeworkPos)->toBeLessThan($wednesdayHomeworkPos);
    expect($mondayPos)->toBeLessThan($mondayHomeworkPos);
    expect($wednesdayPos)->toBeLessThan($wednesdayHomeworkPos);
});

it('renders all seven days for full week range', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29'); // Monday
    $end = new DateTime('2026-07-05');   // Sunday
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $end);

    expect($result)->toContain('Понедельник');
    expect($result)->toContain('Вторник');
    expect($result)->toContain('Среда');
    expect($result)->toContain('Четверг');
    expect($result)->toContain('Пятница');
    expect($result)->toContain('Суббота');
    expect($result)->toContain('Воскресенье');
});

it('renders full week with homeworks every day matching expected output', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29');
    $end = new DateTime('2026-07-04');

    $homeworks = makeHomeworks([
        ['date' => '2026-06-29', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-29', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-29', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-29', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-30', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-30', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-30', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-06-30', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-01', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-02', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-02', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-02', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-02', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-03', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-03', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-03', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-03', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-04', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-04', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-04', 'description' => 'Русский язык: задание 1'],
        ['date' => '2026-07-04', 'description' => 'Русский язык: задание 1'],
    ]);

    $result = $generator->homeworkView($homeworks, $start, $end);

    $expected = "❗️ДЗ на 29.06-04.07\n"
        . "⬜️ Понедельник(29.06)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "⬜️ Вторник(30.06)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "⬜️ Среда(01.07)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "⬜️ Четверг(02.07)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "⬜️ Пятница(03.07)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "⬜️ Суббота(04.07)\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n"
        . "▫️ Русский язык: задание 1\n";

    expect($result)->toBe($expected);
});

it('renders full week with no homeworks', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29');
    $end = new DateTime('2026-07-04');
    $homeworks = Collection::make();

    $result = $generator->homeworkView($homeworks, $start, $end);

    expect($result)->toContain("▫️ Ничего не задали :)\n");
    expect(substr_count($result, "Ничего не задали"))->toBe(6);
});

it('renders mixed week with some days having homeworks', function () {
    $generator = new MessageTextGenerator;
    $start = new DateTime('2026-06-29');
    $end = new DateTime('2026-07-04');

    $homeworks = makeHomeworks([
        ['date' => '2026-06-29', 'description' => 'Понедельник ДЗ'],
        ['date' => '2026-07-01', 'description' => 'Среда ДЗ'],
        ['date' => '2026-07-04', 'description' => 'Суббота ДЗ'],
    ]);

    $result = $generator->homeworkView($homeworks, $start, $end);

    expect($result)->toContain("▫️ Понедельник ДЗ\n");
    expect($result)->toContain("▫️ Среда ДЗ\n");
    expect($result)->toContain("▫️ Суббота ДЗ\n");
    expect(substr_count($result, "Ничего не задали"))->toBe(3);
});
