<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:poll', function (LoggerInterface $logger) {
    $telegram = Telegram::bot();

    while (true) {
        $update = $telegram->commandsHandler(false);

        if ($update === null) {
            sleep(1);

            continue;
        }

        $message = $update->getMessage();

        if ($message === null) {
            sleep(1);

            continue;
        }

        $text = $message->text;

        if ($text === null) {
            sleep(1);

            continue;
        }

        if (str_starts_with($text, '/')) {
            sleep(1);

            continue;
        }

        $logger->debug('Unhandled message', [
            'user_id' => $message->from->id,
            'text' => $text,
        ]);

        sleep(1);
    }
});
