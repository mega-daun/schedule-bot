<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Traits\HasClass;
use App\Traits\HasConversation;
use App\Traits\HasUser;

class JoinClassCommand extends BaseCommand
{
    use HasClass, HasConversation, HasUser;

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

        if ($this->user->class_id !== null) {
            $this->replyWithMessage([
                'text' => 'Вы уже состоите в классе.',
            ]);

            return null;
        }

        if ($token === null) {
            $this->user->startConversation('joinclass', []);

            $this->replyWithMessage([
                'text' => 'Введите токен для присоединения к классу.',
            ]);

            return null;
        }

        if (! $this->isValidTokenFormat($token)) {
            $this->replyWithMessage([
                'text' => 'Класс не найден.',
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

        $this->user->update([
            'class_id' => $this->class->id,
            'role' => UserRole::Student,
        ]);

        $this->replyWithMessage([
            'text' => 'Вы успешно присоеденились к классу '.$this->class->code.'.',
        ]);

        return null;
    }

    private function isValidTokenFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{16}$/i', $token) === 1;
    }
}
