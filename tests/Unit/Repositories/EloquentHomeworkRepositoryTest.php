<?php

declare(strict_types=1);

use App\Models\Classroom;
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
