<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SergiX44\Nutgram\Nutgram;

class StartCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $token = $bot->get('token');

        if ($token === null) {
            $bot->sendMessage(
                text: 'Привет, '.$user->first_name.'! Используй команду /joinclass {токен} для присоединения к существующему классу или создай новый при помощи команды /newclass {название класса}(узнать подробнее - /help).'
            );
        } else {
            if ($user->class !== null) {
                throw new IncorrectMessageException(
                    'Вы перешли по ссылке для присоединения к классу, однако вы уже состоите в классе.'
                );
            }

            $class = Classroom::where('join_token', $token)->first();

            if ($class === null) {
                throw new IncorrectMessageException(
                    'Класс не найден.'
                );
            }

            $user->update([
                'class_id' => $class->id,
                'role' => UserRole::Student,
            ]);

            $bot->sendMessage(
                text: 'Вы успешно присоеденились к классу '.$class->code.'.'
            );
        }
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        try {
            return User::findOrFail($telegramUser->id);
        } catch (ModelNotFoundException) {
            return User::create([
                'id' => $telegramUser->id,
                'first_name' => $telegramUser->first_name,
                'language_code' => $telegramUser->language_code,
                'username' => $telegramUser->username,
            ]);
        }
    }
}
