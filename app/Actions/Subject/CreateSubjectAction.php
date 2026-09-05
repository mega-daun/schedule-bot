<?php

namespace App\Actions\Subject;

use App\Exceptions\InvalidInputException;
use App\Models\Subject;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateSubjectAction
{
    private const MIN_NAME_LENGTH = 3;

    public function __invoke(string $name, int $class_id): Subject
    {
        if (empty($name)) {
            throw new InvalidInputException(__('error.subject.name_empty'));
        }
        if (strlen($name) < self::MIN_NAME_LENGTH) {
            throw new InvalidInputException(__('error.subject.name_too_short', ['min' => self::MIN_NAME_LENGTH]));
        }
        try {
            $subject = Subject::create([
                'name' => $name,
                'class_id' => $class_id,
            ]);

            return $subject;
        } catch (UniqueConstraintViolationException $e) {
            throw new InvalidInputException(__('error.subject.already_exists'));
        }
    }
}
