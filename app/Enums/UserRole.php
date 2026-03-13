<?php

namespace App\Enums;

enum UserRole: int
{
    case Student = 0;
    case OnDuty = 1;
    case Teacher = 2;
    case Admin = 3;
}
