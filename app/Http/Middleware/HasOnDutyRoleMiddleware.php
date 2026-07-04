<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\IncorrectMessageException;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasOnDutyRoleMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user) {
            throw new IncorrectMessageException(__('error.class.not_member'));
        }

        if (! $user->hasClass()) {
            throw new IncorrectMessageException(__('error.class.not_member'));
        }

        if (! $user->isOnDutyOrHigher()) {
            throw new IncorrectMessageException(__('error.class.no_permission'));
        }

        $next($bot);
    }
}
