<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Nutgram;

class CancelCommand
{
    public function __invoke(Nutgram $bot): void
    {
        if ($bot->currentConversation($bot->userId(), $bot->chatId(), $bot->messageThreadId()) == null) {
            $bot->sendMessage(
                text: __('error.cancel.no_active'),
            );

            return;
        }
        $bot->endConversation();

        $bot->sendMessage(
            text: __('info.cancel.done')
        );
    }
}
