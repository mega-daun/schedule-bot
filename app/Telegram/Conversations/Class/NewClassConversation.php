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
            throw new IncorrectMessageException('Вы уже состоите в классе.');
        }

        $bot->sendMessage(
            text: 'Введите название нового класса (например, 10Б):'
        );
        $this->next('handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $input = $bot->message()->text;

        if (! $this->validateInput($input)) {
            $bot->sendMessage(
                text: 'Неверный формат названия класса. Примеры: 1А, 10Б, 11В. Введите название нового класса:'
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
                text: 'Произошла ошибка при создании класса.'
            );
            $this->end();

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Admin,
        ]);

        $bot->sendMessage(
            text: 'Класс '.$class->code.' успешно создан. Токен для присоединения: '.$class->join_token.'. Ссылка для присоединения: https://t.me/hatenigas_bot?start='.$class->join_token
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
