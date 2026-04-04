<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HasClassMembersMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $telegramUser = $bot->user();
        $user = User::find($telegramUser->id);

        if (! $user || ! $user->class_id) {
            $bot->sendMessage('Вы должны состоять в классе.');
            $bot->endConversation();

            return;
        }

        $memberCount = User::where('class_id', $user->class_id)
            ->where('id', '!=', $user->id)
            ->count();

        if ($memberCount === 0) {
            $bot->sendMessage('Нет других участников');
            $bot->endConversation();

            return;
        }

        $next($bot);
    }
}
