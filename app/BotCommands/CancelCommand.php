<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\BotCommands\Exceptions\IncorrectMessageException;

class CancelCommand extends BaseCommand
{
    protected string $name = 'cancel';

    protected string $description = 'Отменяет текущее действие (создание класса, домашнего задания и т.д.)';

    protected function __getArgs(): array
    {
        return [];
    }

    protected function __handle(array $args): void
    {
        if (! $this->user->hasActiveConversation()) {
            throw new IncorrectMessageException(
                'Нет активных действий для отмены.',
            );
        }
        $action = $this->user->getConversationAction();

        $this->user->clearConversationState();

        $this->replyWithMessage([
            'text' => 'Действие "'.$action.'" отменено.',
        ]);
    }
}
