<?php

namespace App\Actions\Subject;

use App\Exceptions\InvalidInputException;
use App\Models\Subject;

class DeleteSubjectAction
{
    public function __construct(private int $id, private int $class_id) {}

    public function __invoke(): void
    {
        $deleted = Subject::where('id', $this->id)->where('class_id', $this->class_id)->delete();
        if ($deleted === 0) {
            throw new InvalidInputException(__('error.subject.not_found'));
        }
    }
}
