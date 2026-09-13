<?php

declare(strict_types=1);

namespace App\Telegram\Commands\Class;

use App\Exceptions\IncorrectMessageException;
use App\Exceptions\UnknownRoleException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class ChangeRoleCommand
{
    public function __invoke(Nutgram $bot, ?string $username = null, ?string $role = null): void
    {
        $user = $this->getUser($bot);

        $targetUser = $this->findClassmember($this->getUsername($username), $user->class_id);

        if ($targetUser->id == $user->id) {
            throw new IncorrectMessageException(__('error.role.self_change'), true);
        }

        $this->changeRole($targetUser, $this->getRole($role));

        $bot->sendMessage(
            text: __('info.role.changed')
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function getUsername(?string $username): string
    {
        if ($username === null) {
            throw new IncorrectMessageException(__('error.role.example'));
        }

        return $username;
    }

    private function getRole(?string $role): string
    {
        if ($role === null) {
            throw new IncorrectMessageException(__('error.role.example'));
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
                throw new IncorrectMessageException(__('error.role.not_in_class'), true);
            }

            throw new IncorrectMessageException(__('error.role.user_not_found'), true);
        }

        return $targetUser;
    }

    private function changeRole(User $user, string $role): void
    {
        try {
            $user->changeRole($role);
        } catch (UnknownRoleException) {
            throw new IncorrectMessageException(__('error.role.invalid'), true);
        }
    }
}
