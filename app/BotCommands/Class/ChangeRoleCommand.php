<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Enums\UserRole;
use App\Models\User;

class ChangeRoleCommand extends BaseCommand
{
    protected string $name = 'changerole';

    protected string $description = 'Изменяет роль пользователя: ученик, дежурный, учитель, админ. Пример команды: /changerole @YoppaniySir ученик';

    protected string $pattern = '{username} {role}';

    protected function __getArgs(): array
    {
        return [
            'username' => $this->argument('username'),
            'role' => $this->argument('role'),
        ];
    }

    protected function __handle(array $args): void
    {
        $username = $args['username'];
        $role = $args['role'];

        if ($this->user->role !== UserRole::Admin) {
            throw new IncorrectMessageException('Только админы могут изменять роли других пользователей.');
        }

        if ((! $username) || (! $role)) {
            throw new IncorrectMessageException('Пример команды: /changerole @YoppaniySir ученик');
        }

        User::where('username', $username)->update(['role' => $role]);
    }
}
