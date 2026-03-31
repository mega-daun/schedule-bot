<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class NewClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $code = $bot->get('code');

        if ($user->class !== null) {
            throw new IncorrectMessageException('Вы уже состоите в классе.');
        }

        if ($code === null) {
            $bot->sendMessage(
                text: 'Введите название нового класса (например, 10Б):'
            );

            return;
        }

        $class = Classroom::create([
            'code' => $code,
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $class) {
            $bot->sendMessage(
                text: 'Произошла ошибка при создании класса.'
            );

            return;
        }

        if (! $user->update(['class_id' => $class->id, 'role' => UserRole::Admin])) {
            $bot->sendMessage(
                text: 'Произошла ошибка при присоединении к классу.'
            );

            return;
        }

        $bot->sendMessage(
            text: 'Класс '.$class->code.' успешно создан. Токен для присоединения: '.$class->join_token.'. Ссылка для присоединения: https://t.me/hatenigas_bot?start='.$class->join_token
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
