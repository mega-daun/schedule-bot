<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\EloquentSubjectRepository;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->repo = new EloquentSubjectRepository;
});

it('returns an empty Eloquent collection when the class has no subjects', function () {
    $classroom = Classroom::factory()->create();

    $result = $this->repo->getSubjects($classroom->id);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toBeEmpty();
    expect($result->toArray())->toBe([]);
    $this->assertDatabaseCount('subjects', 0);
});

it('returns only subjects that belong to the given class', function () {
    $classA = Classroom::factory()->create();
    $classB = Classroom::factory()->create();
    Subject::factory()->create(['class_id' => $classA->id, 'name' => 'Mathematics']);
    Subject::factory()->create(['class_id' => $classA->id, 'name' => 'Physics']);
    Subject::factory()->create(['class_id' => $classB->id, 'name' => 'History']);
    Subject::factory()->create(['class_id' => $classB->id, 'name' => 'Biology']);

    $result = $this->repo->getSubjects($classA->id);

    expect($result)->toHaveCount(2);
    expect($result->pluck('name')->sort()->values()->all())->toBe(['Mathematics', 'Physics']);
    expect($result->pluck('name')->sort()->values()->all())->not->toBe([
        'History',
        'Biology',
    ]);
    expect($result->pluck('name'))->not->toContain('History');
    expect($result->pluck('name'))->not->toContain('Biology');
});

it('returns all default columns when called without the columns argument', function () {
    $classroom = Classroom::factory()->create();
    Subject::factory()->create(['class_id' => $classroom->id, 'name' => 'Geography']);

    $result = $this->repo->getSubjects($classroom->id);

    expect($result)->toHaveCount(1);
    $subject = $result->first();
    expect(isset($subject->id))->toBeTrue();
    expect($subject->class_id)->toBe($classroom->id);
    expect($subject->name)->toBe('Geography');
    // Documented current behavior: all columns (including timestamps) come back.
    expect(isset($subject->created_at))->toBeTrue();
    expect(isset($subject->updated_at))->toBeTrue();
});

it('respects the columns parameter and returns only the selected attributes', function () {
    $classroom = Classroom::factory()->create();
    Subject::factory()->create(['class_id' => $classroom->id, 'name' => 'Chemistry']);

    $result = $this->repo->getSubjects($classroom->id, ['name']);

    expect($result)->toHaveCount(1);
    $subject = $result->first();
    expect(isset($subject->name))->toBeTrue();
    expect($subject->name)->toBe('Chemistry');
    expect(isset($subject->class_id))->toBeFalse();
    expect(isset($subject->id))->toBeFalse();
    // Documented behavior: missing attributes read as null, they do not throw.
    expect($subject->class_id)->toBeNull();
});

it('returns the subjects with their correct names for the class', function () {
    $classroom = Classroom::factory()->create();
    Subject::factory()->create(['class_id' => $classroom->id, 'name' => 'Algebra']);
    Subject::factory()->create(['class_id' => $classroom->id, 'name' => 'Geometry']);

    $result = $this->repo->getSubjects($classroom->id);

    expect($result->pluck('name')->sort()->values()->all())->toBe(['Algebra', 'Geometry']);
    $this->assertDatabaseHas('subjects', ['class_id' => $classroom->id, 'name' => 'Algebra']);
    $this->assertDatabaseHas('subjects', ['class_id' => $classroom->id, 'name' => 'Geometry']);
});

it('returns an empty collection for a non-existent class id without throwing', function () {
    $result = $this->repo->getSubjects(999999);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toBeEmpty();
    $this->assertDatabaseCount('subjects', 0);
});
