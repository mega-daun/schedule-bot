<?php

use App\Actions\Subject\CreateSubjectAction;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;
use Tests\TestCase;

uses(TestCase::class);

it('rejects empty input', function () {
    $action = new CreateSubjectAction(name: '', class_id: 1);
    $action();
})->throws(InvalidInputException::class);

it('rejects names shorter than 3 characters', function () {
    $action = new CreateSubjectAction(name: 'AB', class_id: 1);
    $action();
})->throws(InvalidInputException::class);

it('returns error when subject with same name and class exists', function () {
    $classroom = Classroom::factory()->create();

    Subject::factory()->create([
        'name' => 'Mathematics',
        'class_id' => $classroom->id,
    ]);

    $action = new CreateSubjectAction(name: 'Mathematics', class_id: $classroom->id);
    $action();
})->throws(InvalidInputException::class);

it('returns created subject on success', function () {
    $classroom = Classroom::factory()->create();

    $action = new CreateSubjectAction(name: 'Mathematics', class_id: $classroom->id);
    $subject = $action();

    expect($subject)->toBeInstanceOf(Subject::class)
        ->and($subject->name)->toBe('Mathematics')
        ->and($subject->class_id)->toBe($classroom->id);
});
