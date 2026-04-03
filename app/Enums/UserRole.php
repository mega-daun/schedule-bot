<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Student = 'ученик';
    case OnDuty = 'дежурный';
    case Teacher = 'учитель';
    case Admin = 'админ';
}
