<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class JoinClassConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $bot->sendMessage(
            text: 'Введите токен для присоединения к классу:'
        );
        $this->next('handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $input = trim($bot->message()->text);

        if ($user->class_id !== null) {
            $bot->sendMessage(
                text: 'Вы уже состоите в классе.'
            );

            return;
        }

        if ($input === '') {
            $bot->sendMessage(
                text: 'Токен не может быть пустым. Введите токен для присоединения к классу:'
            );

            return;
        }

        if (! $this->isValidTokenFormat($input)) {
            $bot->sendMessage(
                text: 'Неверный формат токена. Токен должен содержать 16 символов (латинские буквы и цифры). Попробуйте ещё раз или введите /cancel для отмены.'
            );

            return;
        }

        $class = Classroom::where('join_token', $input)->first();

        if (! $class) {
            $bot->sendMessage(
                text: 'Класс с таким токеном не найден. Проверьте токен и попробуйте ещё раз или введите /cancel для отмены.'
            );

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Student,
        ]);

        $bot->sendMessage(
            text: 'Вы успешно присоеденились к классу '.$class->code.'.'
        );

        $this->end();
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
