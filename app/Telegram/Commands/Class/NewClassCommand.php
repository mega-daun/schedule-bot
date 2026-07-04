<?php

declare(strict_types=1);

namespace App\Telegram\Commands\Class;

use App\Enums\UserRole;
use App\Exceptions\IncorrectMessageException;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class NewClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $code = $bot->get('code');

        if ($user->class !== null) {
            throw new IncorrectMessageException(__('error.class.already_member'));
        }

        if ($code === null) {
            $bot->sendMessage(
                text: __('prompt.class.enter_name')
            );

            return;
        }

        $class = Classroom::create([
            'code' => $code,
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $class) {
            $bot->sendMessage(
                text: __('error.class.create_error')
            );

            return;
        }

        if (! $user->update(['class_id' => $class->id, 'role' => UserRole::Admin])) {
            $bot->sendMessage(
                text: __('error.class.join_error')
            );

            return;
        }

        $bot->sendMessage(
            text: __('info.class.created', ['code' => $class->code, 'token' => $class->join_token])
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
