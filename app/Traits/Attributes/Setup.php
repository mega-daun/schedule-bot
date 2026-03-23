<?php

namespace App\Traits\Attributes;

use Attribute;

#[Attribute]
class Setup
{
    public function __construct(public int $order = 0) {}
}
