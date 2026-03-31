<?php

declare(strict_types=1);

namespace App\BotCommands;

use SergiX44\Nutgram\Nutgram;

class CancelCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->endConversation();

        $bot->sendMessage(
            text: 'Действие отменено.'
        );
    }
}
