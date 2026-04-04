<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class IsAdminMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user || ! $user->isAdmin()) {
            throw new IncorrectMessageException('Только админы могут изменять роли других пользователей.', true);
        }

        $next($bot);
    }
}
