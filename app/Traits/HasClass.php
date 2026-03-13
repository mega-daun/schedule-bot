<?php

namespace App\Traits;

use App\Models\Classroom;
use App\Models\User;

trait HasClass
{
    protected ?Classroom $class;

    protected function setClass(User $user)
    {
        $this->class = $user->class;
    }
}
