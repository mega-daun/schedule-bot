<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\BotCommands\Exceptions\IncorrectMessageException;
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

        Conversation::end($bot);

        $bot->sendMessage(
            text: 'Действие отменено.'
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
