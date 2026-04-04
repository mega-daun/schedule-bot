<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Exceptions\UnknownRoleException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class ChangeRoleCommand
{
    private Nutgram $bot;

    public function __invoke(Nutgram $bot): void
    {
        $this->bot = $bot;
        $user = $this->getUser();

        $targetUser = $this->findClassmember($this->getUsername(), $user->class_id);

        if ($targetUser->id == $user->id) {
            throw new IncorrectMessageException('Нельзя изменить роль самого себя.', true);
        }

        $this->changeRole($targetUser, $this->getRole());

        $bot->sendMessage(
            text: 'Роль изменена'
        );
    }

    private function getUser(): User
    {
        $telegramUser = $this->bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function getUsername(): string
    {
        $username = $this->bot->get('username');
        if ($username === null) {
            throw new IncorrectMessageException('Пример команды: /changerole @username учитель');
        }

        return $username;
    }

    private function getRole(): string
    {
        $role = $this->bot->get('role');
        if ($role === null) {
            throw new IncorrectMessageException('Пример команды: /changerole @username учитель');
        }

        return $role;
    }

    private function findClassmember(string $username, int $class_id): User
    {
        $targetUser = User::where('username', $username)
            ->where('class_id', $class_id)
            ->first();

        if (! $targetUser) {
            $targetUser = User::where('username', $username)->first();

            if ($targetUser) {
                throw new IncorrectMessageException('Пользователь не состоит в вашем классе.', true);
            }

            throw new IncorrectMessageException('Пользователь не найден.', true);
        }

        return $targetUser;
    }

    private function changeRole(User $user, string $role): void
    {
        try {
            $user->changeRole($role);
        } catch (UnknownRoleException) {
            throw new IncorrectMessageException('Неверная роль. Доступные: ученик, учитель, дежурный, админ.', true);
        }
    }
}
