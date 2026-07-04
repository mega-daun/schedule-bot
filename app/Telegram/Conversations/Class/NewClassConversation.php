<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Enums\UserRole;
use App\Exceptions\IncorrectMessageException;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class NewClassConversation extends Conversation
{
    private const VALID_PATTERN = '/^[1-9][01]?[А-Яа-я]$/u';

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);

        if ($user->class !== null) {
            throw new IncorrectMessageException(__('error.class.already_member'));
        }

        $bot->sendMessage(
            text: __('prompt.class.enter_name')
        );
        $this->next('handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $input = $bot->message()->text;

        if (! $this->validateInput($input)) {
            $bot->sendMessage(
                text: __('prompt.class.name_invalid')
            );

            return;
        }

        $user = $this->getUser($bot);

        $class = Classroom::create([
            'code' => Str::upper($input),
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $class) {
            $bot->sendMessage(
                text: __('error.class.create_error')
            );
            $this->end();

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Admin,
        ]);

        $bot->sendMessage(
            text: __('info.class.created', ['code' => $class->code, 'token' => $class->join_token])
        );

        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function validateInput(string $input): bool
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            return false;
        }

        if (strlen($trimmed) > 5) {
            return false;
        }

        return preg_match(self::VALID_PATTERN, $trimmed) === 1;
    }
}
