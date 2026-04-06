<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasSubjectsMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user || ! $user->class) {
            $next($bot);

            return;
        }

        if ($user->class->subjects->isEmpty()) {
            $bot->sendMessage(
                text: 'В классе нет предметов.'
            );

            return;
        }

        $next($bot);
    }
}
