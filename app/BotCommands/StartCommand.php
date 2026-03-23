<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;

class StartCommand extends BaseCommand
{
    protected string $name = 'start';

    protected string $description = 'Начинает общение с ботом. Даёт начальные инструкции.';

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

        if ($token === null) {
            $this->replyWithMessage([
                'text' => 'Привет, '.$this->user->first_name.'! Используй команду /joinclass {токен} для присоединения к существующему классу или создай новый при помощи команды /newclass {название класса}(узнать подробнее - /help).',
            ]);
        } else {
            if ($this->user->class !== null) {
                throw new IncorrectMessageException(
                    'Вы перешли по ссылке для присоединения к классу, однако вы уже состоите в классе.'
                );
            }

            $this->class = Classroom::where('join_token', $token)->first();

            if ($this->class === null) {
                throw new IncorrectMessageException(
                    'Класс не найден.'
                );
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
}
