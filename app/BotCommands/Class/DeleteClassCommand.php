<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class DeleteClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if (! $user->class) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        if ($user->role !== UserRole::Admin) {
            throw new IncorrectMessageException('Вы не имеете право это сделать.');
        }

        $class = $user->class;

        if (! $class->delete()) {
            $bot->sendMessage(
                text: 'Произошла ошибка на стороне сервера.'
            );

            return;
        }

        $user->update(['role' => UserRole::Student]);

        $bot->sendMessage(
            text: 'Вы успешно удалили класс '.$class->code.'.'
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
