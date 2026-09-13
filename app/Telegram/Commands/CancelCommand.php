<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Nutgram;

class CancelCommand extends BaseCommand
{
    public function __invoke(Nutgram $bot): void
    {
        if ($bot->currentConversation($bot->userId(), $bot->chatId(), $bot->messageThreadId()) == null) {
            $this->reply($bot, __('error.cancel.no_active'));

            return;
        }
        $bot->endConversation();

        $this->reply($bot, __('info.cancel.done'));
    }
}
