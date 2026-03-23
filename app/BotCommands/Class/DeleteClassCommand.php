<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;

class DeleteClassCommand extends BaseCommand
{
    protected string $name = 'deleteclass';

    protected string $description = 'Удалить класс. Пример: /deleteclass';

    protected function __getArgs(): array
    {
        return [];
    }

    protected function __handle(array $args): void
    {
        if (! $this->class) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        if ($this->user->role !== UserRole::Admin) {
            throw new IncorrectMessageException('Вы не имеете право это сделать.');
        }

        if (! $this->class->delete()) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка на стороне сервера.',
            ]);
        }

        $this->user->update(['role' => UserRole::Student]);

        $this->replyWithMessage([
            'text' => 'Вы успешно удалили класс '.$this->class->code.'.',
        ]);
    }
}
