<?php

declare(strict_types=1);

namespace App\BotCommands;

use SergiX44\Nutgram\Nutgram;

class CancelCommand
{
    public function __invoke(Nutgram $bot): void
    {
        if ($bot->currentConversation($bot->userId(), $bot->chatId(), $bot->messageThreadId()) == null) {
            $bot->sendMessage(
                text: 'Нет активных действий для отмены.',
            );

            return;
        }
        $bot->endConversation();

        $bot->sendMessage(
            text: 'Действие отменено.'
        );
    }
}
