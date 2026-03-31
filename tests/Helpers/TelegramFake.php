<?php

declare(strict_types=1);

use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\ChatMember;

function runCommand(array $from, string $text, array $commandsUsed = []): ?array
{
    $bot = Nutgram::fake();

    foreach ($commandsUsed as $command) {
        if (is_string($command) && class_exists($command)) {
            $bot->onCommand($command, new $command);
        }
    }

    $bot->setCommonUser(
        new ChatMember(
            [
                'id' => $from['id'],
                'first_name' => $from['first_name'],
                'is_bot' => $from['is_bot'] ?? false,
                'username' => $from['username'] ?? null,
                'language_code' => $from['language_code'] ?? null,
            ]
        )
    );

    $bot->setCommonChat(
        new Chat(
            [
                'id' => $from['id'],
                'first_name' => $from['first_name'],
                'type' => 'private',
            ]
        )
    );

    $sentMessage = null;
    $bot->onMessage(function (Nutgram $bot) use (&$sentMessage) {
        $sentMessage = [
            'text' => $bot->getMessage()->text,
            'chat_id' => $bot->getMessage()->chat->id,
        ];
    });

    $bot->hearText($text)->reply();

    return $sentMessage;
}

function runCommandAs(User $user, string $text, array $commandsUsed = []): ?array
{
    return runCommand($user->toArray(), $text, $commandsUsed);
}

function sendConversationMessage(User $user, string $text, array $conversationHandlersUsed = []): ?array
{
    $bot = Nutgram::fake();

    foreach ($conversationHandlersUsed as $action => $handler) {
        if (is_string($handler) && class_exists($handler)) {
            $bot->onCommand($action, new $handler);
        }
    }

    $bot->setCommonUser(
        new ChatMember(
            [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'is_bot' => false,
                'username' => $user->username,
                'language_code' => $user->language_code,
            ]
        )
    );

    $bot->setCommonChat(
        new Chat(
            [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'type' => 'private',
            ]
        )
    );

    $sentMessage = null;
    $bot->onMessage(function (Nutgram $bot) use (&$sentMessage) {
        $sentMessage = [
            'text' => $bot->getMessage()->text,
            'chat_id' => $bot->getMessage()->chat->id,
        ];
    });

    $bot->hearText($text)->reply();

    return $sentMessage;
}
