<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Models\Classroom;
use App\Traits\HasClass;
use App\Traits\HasUser;

class JoinClassCommand extends BaseCommand
{
    use HasClass, HasUser;

    protected string $name = 'joinclass';

    protected string $description = 'Присоедениться к классу по токену. Пример: /joinclass higitler1488';

    protected string $pattern = '{token}';

    protected function __getArgs(): array
    {
        return [
            'token' => $this->argument('token'),
        ];
    }

    protected function __handle(array $args): mixed
    {
        $this->setUser($this->getUpdate()->getMessage()->from);
        $token = $args['token'];

        if ($token === null) {
            $this->replyWithMessage([
                'text' => 'Для присоединения к классу, укажите токен.',
            ]);

            return null;
        }

        $this->class = Classroom::where('join_token', $token)->first();

        if (! $this->class) {
            $this->replyWithMessage([
                'text' => 'Класс не найден.',
            ]);

            return null;
        }

        if (! $this->class->users()->save($this->user)) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка при присоединении к классу на стороне сервера.',
            ]);

            return null;
        }

        $this->replyWithMessage([
            'text' => 'Вы успешно присоеденились к классу '.$this->class->code.'.',
        ]);

        return null;
    }
}
