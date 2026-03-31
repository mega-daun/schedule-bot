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
        $username = $bot->get('username');
        $role = $bot->get('role');

        if ($user->role !== UserRole::Admin) {
            throw new IncorrectMessageException('Только админы могут изменять роли других пользователей.');
        }

        if ((! $username) || (! $role)) {
            throw new IncorrectMessageException('Пример команды: /changerole @YoppaniySir ученик');
        }

        User::where('username', $username)->update(['role' => $role]);
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
