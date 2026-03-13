<?php

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Enums\UserRole;
use App\Traits\HasClass;
use App\Traits\HasUser;

class DeleteClassCommand extends BaseCommand
{
    use HasClass, HasUser;

    protected string $name = 'deleteclass';

    protected string $description = 'Удалить класс. Пример: /deleteclass';

    protected function __getArgs(): array
    {
        return [];
    }

    protected function __handle(array $args): void
    {
        $this->setUser($this->getUpdate()->getMessage()->from);
        $class = $this->user->class;

        if (! $class) {
            $this->replyWithMessage([
                'text' => 'Вы не состоите в классе.',
            ]);

            return;
        }

        if ($this->user->role !== UserRole::Admin) {
            $this->replyWithMessage([
                'text' => 'Вы не имеете право это сделать.',
            ]);

            return;
        }

        if (! $class->delete()) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка на стороне сервера.',
            ]);
        }

        $this->user->update(['role' => UserRole::Student]);

        $this->replyWithMessage([
            'text' => 'Вы успешно удалили класс '.$class->code.'.',
        ]);
    }
}
