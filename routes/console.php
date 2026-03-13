<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:poll', function (LoggerInterface $logger) {
    while (true) {
        $update = Telegram::commandsHandler(false);
        sleep(1);
    }
});
