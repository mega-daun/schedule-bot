<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\IncorrectMessageException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasClassMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user || ! $user->hasClass()) {
            throw new IncorrectMessageException('Вы не состоите в классе.', true);
        }

        $next($bot);
    }
}
