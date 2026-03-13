<?php

namespace App\BotCommands;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Traits\HasClass;
use App\Traits\HasUser;

class StartCommand extends BaseCommand
{
    use HasClass, HasUser;

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
        $this->setUser($this->getUpdate()->getMessage()->from);
        $token = $args['token'];

        if ($token === null) {
            $this->replyWithMessage([
                'text' => 'Привет, '.$this->user?->firstName.'! Используй команду /joinclass {токен} для присоединения к существующему классу или создай новый при помощи команды /newclass {название класса}(узнать подробнее - /help).',
            ]);

            return;
        }

        if ($this->user->class !== null) {
            $this->replyWithMessage([
                'text' => 'Вы перешли по ссылке для присоединения к классу, однако вы уже состоите в классе.',
            ]);

            return;
        }

        $this->class = Classroom::where('join_token', $token)->first();

        if ($this->class === null) {
            $this->replyWithMessage([
                'text' => 'Класс не найден.',
            ]);

            return;
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
