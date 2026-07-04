<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Enums\UserRole;
use App\Exceptions\IncorrectMessageException;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SergiX44\Nutgram\Nutgram;

class StartCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);
        $token = $bot->get('token');

        if ($token === null) {
            $bot->sendMessage(
                text: __('prompt.general.welcome', ['name' => $user->first_name])
            );
        } else {
            if ($user->class !== null) {
                throw new IncorrectMessageException(
                    __('error.class.already_has_class_link')
                );
            }

            $class = Classroom::where('join_token', $token)->first();

            if ($class === null) {
                throw new IncorrectMessageException(
                    __('error.class.not_found')
                );
            }

            $user->update([
                'class_id' => $class->id,
                'role' => UserRole::Student,
            ]);

            $bot->sendMessage(
                text: __('info.class.joined', ['code' => $class->code])
            );
        }
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        try {
            return User::findOrFail($telegramUser->id);
        } catch (ModelNotFoundException) {
            return User::create([
                'id' => $telegramUser->id,
                'first_name' => $telegramUser->first_name,
                'language_code' => $telegramUser->language_code,
                'username' => $telegramUser->username,
            ]);
        }
    }
}
