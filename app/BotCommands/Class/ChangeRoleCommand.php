<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Enums\UserRole;
use App\Models\User;
use App\Traits\HasClass;
use App\Traits\HasUser;

class ChangeRoleCommand extends BaseCommand
{
    use HasClass, HasUser;

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

    protected function __handle(array $args): mixed
    {
        $this->setUser($this->getUpdate()->getMessage()->from);
        $username = $args['username'];
        $role = $args['role'];

        if ($this->user->role !== UserRole::Admin) {
            $this->replyWithMessage([
                'text' => 'Только админы могут изменять роли других пользователей.',
            ]);

            return null;
        }

        if ((! $username) || (! $role)) {
            $this->replyWithMessage([
                'text' => 'Пример команды: /changerole @YoppaniySir ученик',
            ]);

            return null;
        }

        User::where('username', $username)->update(['role' => $role]);

        return null;
    }
}
