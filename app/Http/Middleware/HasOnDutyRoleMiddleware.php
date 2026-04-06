<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasOnDutyRoleMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        if (! $user->hasClass()) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        if (! $user->isOnDutyOrHigher()) {
            throw new IncorrectMessageException('Вы не имеете право это сделать.');
        }

        $next($bot);
    }
}
