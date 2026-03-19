<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\Traits\HasUser;

class CancelCommand extends BaseCommand
{
    use HasUser;

    protected string $name = 'cancel';

    protected string $description = 'Отменяет текущее действие (создание класса, домашнего задания и т.д.)';

    protected function __getArgs(): array
    {
        return [];
    }

    protected function __handle(array $args): mixed
    {
        $this->setUser($this->getUpdate()->getMessage()->from);

        if (! $this->user->hasActiveConversation()) {
            $this->replyWithMessage([
                'text' => 'Нет активных действий для отмены.',
            ]);

            return null;
        }

        $action = $this->user->getConversationAction();
        $this->user->clearConversationState();

        $this->replyWithMessage([
            'text' => 'Действие "'.$action.'" отменено.',
        ]);

        return null;
    }
}
