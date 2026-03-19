<?php

declare(strict_types=1);

namespace App\BotCommands\Conversations;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Handles multi-step class creation conversation.
 *
 * Flow:
 * 1. User sends /newclass (without argument)
 * 2. Bot asks for class name
 * 3. User provides class name
 * 4. This conversation handler creates the class and completes
 */
class NewClassConversation extends Conversation
{
    public function handle(User $user, string $input, Api $telegram, Update $update): void
    {
        $chatId = $update->getMessage()->chat->id;
        $botUsername = env('TELEGRAM_BOT_NAME', 'hatenigas_bot');

        $class = Classroom::create([
            'code' => $input,
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $class) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка при создании класса.',
            ]);

            return;
        }

        $user->update([
            'class_id' => $class->id,
            'role' => UserRole::Admin,
            'conversation_state' => null,
        ]);

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Класс '.$class->code.' успешно создан. Токен для присоединения: '.$class->join_token.'. Ссылка для присоединения: https://t.me/'.$botUsername.'?start='.$class->join_token,
        ]);
    }
}
