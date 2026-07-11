<?php

namespace App\Actions\Subject;

use App\Exceptions\InvalidInputException;
use App\Models\Subject;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateSubjectAction
{
    private const MIN_NAME_LENGTH = 3;

    public function __construct(private string $name, private int $class_id) {}

    /**
     * Invoke the class instance.
     */
    public function __invoke(): Subject
    {
        if (empty($this->name)) {
            throw new InvalidInputException(__('error.subject.name_empty'));
        }
        if (strlen($this->name) < self::MIN_NAME_LENGTH) {
            throw new InvalidInputException(__('error.subject.name_too_short', ['min' => self::MIN_NAME_LENGTH]));
        }
        try {
            $subject = Subject::create([
                'name' => $this->name,
                'class_id' => $this->class_id,
            ]);

            return $subject;
        } catch (UniqueConstraintViolationException $e) {
            throw new InvalidInputException(__('error.subject.already_exists'));
        }
    }
}
