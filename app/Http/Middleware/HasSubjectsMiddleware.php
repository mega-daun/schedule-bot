<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\IncorrectMessageException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasSubjectsMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if ($user->class->subjects->isEmpty()) {
            throw new IncorrectMessageException(__('error.class.no_subjects'), true);
        }

        $next($bot);
    }
}
