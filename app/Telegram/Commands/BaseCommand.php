<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Utils\UserUtils;
use SergiX44\Nutgram\Nutgram;

class BaseCommand
{
    use UserUtils;

    protected function reply(Nutgram $bot, string $message): void
    {
        $bot->sendMessage($message);
    }
}
