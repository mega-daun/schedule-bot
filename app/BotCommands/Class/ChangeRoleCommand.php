<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class ChangeRoleCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if (! $user->class) {
            throw new IncorrectMessageException('Вы должны состоять в классе.', true);
        }

        if ($user->role !== UserRole::Admin) {
            throw new IncorrectMessageException('Только админы могут изменять роли других пользователей.', true);
        }

        $username = $bot->get('username');
        $role = $bot->get('role');

        if ($username === null) {
            $bot->sendMessage(
                text: 'Пример команды: /changerole @username учитель'
            );

            return;
        }

        if ($role === null) {
            $bot->sendMessage(
                text: 'Пример команды: /changerole @username учитель'
            );

            return;
        }

        $targetUser = User::where('username', $username)
            ->where('class_id', $user->class_id)
            ->first();

        if (! $targetUser) {
            $targetUser = User::where('username', $username)->first();

            if ($targetUser) {
                throw new IncorrectMessageException('Пользователь не состоит в вашем классе.', true);
            }

            throw new IncorrectMessageException('Пользователь не найден.', true);
        }

        if ($targetUser->id === $user->id) {
            throw new IncorrectMessageException('Нельзя изменить роль самого себя.', true);
        }

        $roleEnum = UserRole::tryFrom($role);

        if ($roleEnum === null) {
            throw new IncorrectMessageException('Неверная роль. Доступные: ученик, учитель, дежурный, админ.', true);
        }

        $targetUser->update(['role' => $roleEnum]);

        $bot->sendMessage(
            text: 'Роль изменена'
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
