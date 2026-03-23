<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;

class JoinClassCommand extends BaseCommand
{
    protected string $name = 'joinclass';

    protected string $description = 'Присоедениться к классу по токену. Пример: /joinclass higitler1488';

    protected string $pattern = '{token}';

    protected function __getArgs(): array
    {
        return [
            'token' => $this->argument('token'),
        ];
    }

    protected function __handle(array $args): void
    {
        $token = $args['token'];

        if ($this->user->class_id !== null) {
            throw new IncorrectMessageException('Вы уже состоите в классе.');
        }

        if ($token === null) {
            $this->user->startConversation('joinclass', []);

            $this->replyWithMessage([
                'text' => 'Введите токен для присоединения к классу.',
            ]);
        } else {
            if (! $this->isValidTokenFormat($token)) {
                throw new IncorrectMessageException('Класс не найден.');
            }

            $this->class = Classroom::where('join_token', $token)->first();

            if (! $this->class) {
                throw new IncorrectMessageException('Класс не найден.');
            }

            $this->user->update([
                'class_id' => $this->class->id,
                'role' => UserRole::Student,
            ]);

            $this->replyWithMessage([
                'text' => 'Вы успешно присоеденились к классу '.$this->class->code.'.',
            ]);
        }
    }

    private function isValidTokenFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{16}$/i', $token) === 1;
    }
}
