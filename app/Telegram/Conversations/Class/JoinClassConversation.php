<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class JoinClassConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $bot->sendMessage(
            text: __('prompt.class.enter_token')
        );
        $this->next('handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $input = trim($bot->message()->text);

        if ($user->class_id !== null) {
            $bot->sendMessage(
                text: __('error.class.already_member')
            );

            return;
        }

        if ($input === '') {
            $bot->sendMessage(
                text: __('prompt.class.token_empty')
            );

            return;
        }

        if (! $this->isValidTokenFormat($input)) {
            $bot->sendMessage(
                text: __('prompt.class.token_invalid')
            );

            return;
        }

        $class = Classroom::where('join_token', $input)->first();

        if (! $class) {
            $bot->sendMessage(
                text: __('error.class.not_found_with_token')
            );

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Student,
        ]);

        $bot->sendMessage(
            text: __('info.class.joined', ['code' => $class->code])
        );

        $this->end();
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
