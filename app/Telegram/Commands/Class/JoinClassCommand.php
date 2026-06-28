<?php

declare(strict_types=1);

namespace App\Telegram\Commands\Class;

use App\Enums\UserRole;
use App\Exceptions\IncorrectMessageException;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class JoinClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $token = $bot->get('token');

        if ($user->class_id !== null) {
            throw new IncorrectMessageException('Вы уже состоите в классе.', true);
        }

        if ($token === null) {
            $bot->sendMessage(
                text: 'Введите токен для присоединения к классу.'
            );

            return;
        }

        if (! $this->isValidTokenFormat($token)) {
            throw new IncorrectMessageException('Класс не найден. Попробуйте ещё раз.');
        }

        $class = Classroom::where('join_token', $token)->first();

        if (! $class) {
            throw new IncorrectMessageException('Класс не найден.');
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Student,
        ]);

        $bot->sendMessage(
            text: 'Вы успешно присоеденились к классу '.$class->code.'.'
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function isValidTokenFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{16}$/i', $token) === 1;
    }
}
