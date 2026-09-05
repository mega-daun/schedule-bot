<?php

namespace App\Actions\Subject;

use App\Exceptions\InvalidInputException;
use App\Models\Subject;

class DeleteSubjectAction
{
    public function __invoke(int $id, int $class_id): void
    {
        $deleted = Subject::where('id', $id)->where('class_id', $class_id)->delete();
        if ($deleted === 0) {
            throw new InvalidInputException(__('error.subject.not_found'));
        }
    }
}
