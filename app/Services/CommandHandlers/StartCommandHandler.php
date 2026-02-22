<?php

declare(strict_types=1);

namespace App\Services\CommandHandlers;

use App\Jobs\SendTelegramMessage;
use App\Models\User;

class StartCommandHandler extends CommandHandler
{
    public function handle(): void
    {
        User::findOrCreate($this->from);

        $message = "Hello! Welcome to Schedule Bot.\n\n"
            ."To get started, use the following command to join your class:\n"
            ."`/class join {class_name}`\n\n"
            .'Example: `/class join 10Б`';

        SendTelegramMessage::dispatch($this->chatId, $message);
    }
}
