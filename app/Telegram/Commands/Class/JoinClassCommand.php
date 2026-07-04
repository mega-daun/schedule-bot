<?php

declare(strict_types=1);

namespace App\Telegram\Commands\Class;

use App\Enums\UserRole;
use App\Exceptions\IncorrectMessageException;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class JoinClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $token = $bot->get('token');

        if ($user->class_id !== null) {
            throw new IncorrectMessageException(__('error.class.already_member'), true);
        }

        if ($token === null) {
            $bot->sendMessage(
                text: __('prompt.class.enter_token')
            );

            return;
        }

        if (! $this->isValidTokenFormat($token)) {
            throw new IncorrectMessageException(__('error.class.not_found_try_again'));
        }

        $class = Classroom::where('join_token', $token)->first();

        if (! $class) {
            throw new IncorrectMessageException(__('error.class.not_found'));
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Student,
        ]);

        $bot->sendMessage(
            text: __('info.class.joined', ['code' => $class->code])
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function isValidTokenFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{16}$/i', $token) === 1;
    }
}
