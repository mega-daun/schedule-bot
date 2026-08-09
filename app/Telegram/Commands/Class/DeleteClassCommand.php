<?php

declare(strict_types=1);

namespace App\Telegram\Commands\Class;

use App\Enums\UserRole;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class DeleteClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $class = $user->class;

        if (! $class->delete()) {
            $bot->sendMessage(
                text: __('error.class.server_error')
            );

            return;
        }

        $user->update(['role' => UserRole::Student]);

        $bot->sendMessage(
            text: __('info.class.deleted', ['code' => $class->code])
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
