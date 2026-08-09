<?php

use App\Actions\Subject\DeleteSubjectAction;
use App\Exceptions\InvalidInputException;
use App\Models\Classroom;
use App\Models\Subject;

it('deletes an existing subject', function () {
    $subject = Subject::factory()->for(Classroom::factory())->create();

    $action = new DeleteSubjectAction(id: $subject->id, class_id: $subject->class_id);
    $action();

    expect(Subject::find($subject->id))->toBeNull();
});

it('throws when subject does not exist', function () {
    $action = new DeleteSubjectAction(id: 999999, class_id: 123);
    $action();
})->throws(InvalidInputException::class);
