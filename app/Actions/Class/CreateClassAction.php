<?php

namespace App\Actions\Class;

use App\Models\Classroom;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateClassAction
{
    private const CLASSNAME_PATTERN = '/^[1-9][01]?[А-Яа-я]$/u';

    public function __invoke(string $class_name): Classroom
    {
        if (! preg_match(self::CLASSNAME_PATTERN, trim($class_name))) {
            throw new InvalidArgumentException(__('prompt.class.name_invalid'));
        }

        $class = Classroom::create([
            'code' => Str::upper(trim($class_name)),
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $class) {
            throw new Exception(__('error.class.create_error'));
        }

        return $class;
    }
}
