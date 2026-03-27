<?php

declare(strict_types=1);

require_once __DIR__.'/Helpers/TelegramFake.php';

use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        Bus::fake();
    })
    ->in('Feature', 'Unit');
