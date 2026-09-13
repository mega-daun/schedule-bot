<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Repositories\EloquentHomeworkRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repo = new EloquentHomeworkRepository;
});

it('returns true on successful insert', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $result = $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );

    expect($result)->toBeBool();
    expect($result)->toBeTrue();
});

it('persists the full homework row', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );

    $this->assertDatabaseHas('homeworks', [
        'class_id' => $classroom->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-13',
        'description' => 'Read chapter five and solve all exercises',
    ]);
});

it('persists exactly one homework row', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );

    $this->assertDatabaseCount('homeworks', 1);
});

it('inserts with a single DB query', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    DB::enableQueryLog();
    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );
    $queryLog = DB::getQueryLog();
    DB::disableQueryLog();

    $insertQueries = array_filter($queryLog, fn ($q) => str_contains($q['query'], 'insert into'));
    expect($insertQueries)->toHaveCount(1);
});

it('throws QueryException on duplicate (class_id, subject_id, date) insert', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );
    $this->assertDatabaseCount('homeworks', 1);

    expect(fn () => $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'A duplicate homework for the same class, subject and date',
        $subject->id
    ))->toThrow(QueryException::class);

    $this->assertDatabaseCount('homeworks', 1);
});

it('persists the description exactly as given without trimming', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $description = '  Read chapter five and solve all exercises  ';

    $this->repo->createHomework($classroom->id, '2026-09-13', $description, $subject->id);

    $this->assertDatabaseHas('homeworks', [
        'class_id' => $classroom->id,
        'subject_id' => $subject->id,
        'description' => $description,
    ]);
});

it('persists the date as provided in Y-m-d format', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13',
        'Read chapter five and solve all exercises',
        $subject->id
    );

    $this->assertDatabaseHas('homeworks', [
        'class_id' => $classroom->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-13',
    ]);
});

it('truncates a datetime string to the date part in the DATE column', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $this->repo->createHomework(
        $classroom->id,
        '2026-09-13 10:00:00',
        'Read chapter five and solve all exercises',
        $subject->id
    );

    $this->assertDatabaseHas('homeworks', [
        'class_id' => $classroom->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-13',
    ]);
});

it('returns only homeworks within the given date range', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-10']);
    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-13']);
    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-20']);

    $homeworks = $this->repo->getHomeworks($classroom->id, '2026-09-12', '2026-09-15');

    expect($homeworks)->toHaveCount(1);
    expect($homeworks->first()->date->toDateString())->toBe('2026-09-13');
});

it('returns only homeworks of the given class', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);
    $otherSubject = Subject::factory()->create(['class_id' => $otherClassroom->id]);

    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-13']);
    Homework::factory()->create(['class_id' => $otherClassroom->id, 'subject_id' => $otherSubject->id, 'date' => '2026-09-13']);

    $homeworks = $this->repo->getHomeworks($classroom->id, '2026-09-12', '2026-09-15');

    expect($homeworks)->toHaveCount(1);
    expect($homeworks->first()->class_id)->toBe($classroom->id);
});

it('returns homeworks ordered by date ascending', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-20']);
    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-10']);
    Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-13']);

    $homeworks = $this->repo->getHomeworks($classroom->id, '2026-09-01', '2026-09-30');

    expect($homeworks->pluck('date')->map(fn ($date) => $date->toDateString())->toArray())
        ->toBe(['2026-09-10', '2026-09-13', '2026-09-20']);
});

it('returns the homework model for an existing id', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $homework = Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-13']);

    $found = $this->repo->findHomework($homework->id);

    expect($found)->toBeInstanceOf(Homework::class);
    expect($found->id)->toBe($homework->id);
});

it('returns null when no homework has the given id', function () {
    $found = $this->repo->findHomework(999999);

    expect($found)->toBeNull();
});

it('deletes an existing homework and returns true', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['class_id' => $classroom->id]);

    $homework = Homework::factory()->create(['class_id' => $classroom->id, 'subject_id' => $subject->id, 'date' => '2026-09-13']);

    $result = $this->repo->deleteHomework($homework->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
});

it('returns false when deleting a non-existent homework', function () {
    $result = $this->repo->deleteHomework(999999);

    expect($result)->toBeFalse();
});
