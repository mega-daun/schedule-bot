<?php

use App\Actions\Subject\CreateSubjectAction;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;

it('rejects empty input', function () {
    $action = new CreateSubjectAction;
    $action(name: '', class_id: 1);
})->throws(InvalidInputException::class);

it('rejects names shorter than 3 characters', function () {
    $action = new CreateSubjectAction;
    $action(name: 'AB', class_id: 1);
})->throws(InvalidInputException::class);

it('returns error when subject with same name and class exists', function () {
    $classroom = Classroom::factory()->create();

    Subject::factory()->create([
        'name' => 'Mathematics',
        'class_id' => $classroom->id,
    ]);

    $action = new CreateSubjectAction;
    $action(name: 'Mathematics', class_id: $classroom->id);
})->throws(InvalidInputException::class);

it('returns created subject on success', function () {
    $classroom = Classroom::factory()->create();

    $action = new CreateSubjectAction;
    $subject = $action(name: 'Mathematics', class_id: $classroom->id);

    expect($subject)->toBeInstanceOf(Subject::class)
        ->and($subject->name)->toBe('Mathematics')
        ->and($subject->class_id)->toBe($classroom->id);
});
