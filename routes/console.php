<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\TelegramUpdateHandler;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:poll {--timeout=20}', function (TelegramUpdateHandler $handler): void {
    $timeout = (int) $this->option('timeout');
    $offset = 0;

    $this->info('Starting Telegram long polling. Press Ctrl+C to stop.');

    while (true) {
        $updates = Telegram::getUpdates([
            'offset' => $offset === 0 ? null : $offset + 1,
            'timeout' => $timeout,
        ]);

        foreach ($updates as $update) {
            $updateArray = $update->toArray();

            $handler->handle($updateArray);

            if (isset($updateArray['update_id']) && $updateArray['update_id'] > $offset) {
                $offset = $updateArray['update_id'];
            }
        }
        sleep(1);
    }
})->purpose('Poll Telegram for updates using getUpdates');

