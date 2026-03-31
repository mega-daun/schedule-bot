<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::post('/webhook', function (Nutgram $bot) {
    $bot->run();
});
