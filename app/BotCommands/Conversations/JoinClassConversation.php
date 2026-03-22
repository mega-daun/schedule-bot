<?php

declare(strict_types=1);

namespace App\BotCommands\Conversations;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class JoinClassConversation extends Conversation
{
    public function handle(User $user, string $input, Api $telegram, Update $update): void
    {
        $chatId = $update->getMessage()->chat->id;

        if ($user->class_id !== null) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Вы уже состоите в классе.',
            ]);

            return;
        }

        $trimmed = trim($input);

        if ($trimmed === '') {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Токен не может быть пустым. Введите токен для присоединения к классу:',
            ]);

            return;
        }

        if (! $this->isValidTokenFormat($trimmed)) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Класс не найден.',
            ]);

            return;
        }

        $class = Classroom::where('join_token', $trimmed)->first();

        if (! $class) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Класс не найден.',
            ]);

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Student,
            'conversation_state' => null,
        ]);

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Вы успешно присоеденились к классу '.$class->code.'.',
        ]);
    }

    private function isValidTokenFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{16}$/i', $token) === 1;
    }
}
