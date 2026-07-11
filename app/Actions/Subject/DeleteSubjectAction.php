<?php

namespace App\Actions\Subject;

use App\Exceptions\InvalidInputException;
use App\Models\Subject;

class DeleteSubjectAction
{
    public function __construct(private int $id)
    {}

    public function __invoke(): void
    {
        $deleted = Subject::destroy($this->id);
        if ($deleted === 0) {
            throw new InvalidInputException(__('error.subject.not_found'));
        }
    }
}
