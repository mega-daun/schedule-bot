<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Classroom;
use App\Traits\Attributes\Setup;
use Telegram\Bot\Objects\Update;

trait HasClass
{
    protected ?Classroom $class;

    #[Setup(order: 2)]
    protected function setClass(Update $update)
    {
        $this->class = $this->user->class;
    }
}
