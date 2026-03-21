<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\ConversationHandler;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\CommandBus;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

/**
 * Executes a Telegram command and returns the sent message response.
 *
 * @param  array{id: int, first_name: string, is_bot: bool, username: string, language_code: string}  $from  User data from Telegram (id and first_name required)
 * @param  array  $commandsUsed  Array of all fully-qualified class names of commands which will be used
 * @param  string  $text  Command text to send (e.g., '/start' or '/start mytoken')
 * @return array{text: string, chat_id: int}|null The message parameters sent by the command, or null if no message was sent
 */
function runCommand(array $from, string $text, array $commandsUsed = []): ?array
{
    $chat = new Chat([
        'id' => $from['id'],
        'first_name' => $from['first_name'],
        'type' => 'private',
    ]);
    $commandPart = explode(' ', $text)[0];
    $updateData = [
        'update_id' => 10001,
        'message' => [
            'message_id' => 1111,
            'from' => $from,
            'chat' => $chat,
            'text' => $text,
            'date' => 1234567890,
            'entities' => [
                [
                    'type' => 'bot_command',
                    'offset' => 0,
                    'length' => strlen($commandPart),
                ],
            ],
        ],
    ];

    $fakeUpdate = new Update($updateData);
    $sentMessage = null;

    $apiMock = Mockery::mock(Api::class)->makePartial();
    $apiMock->shouldReceive('sendMessage')
        ->andReturnUsing(function ($params) use (&$sentMessage) {
            $sentMessage = $params;

            return new Message(array_merge(['message_id' => 1], $params));
        });

    $apiMock->shouldReceive('getAccessToken')->andReturn('test:test');

    $commandBus = new CommandBus($apiMock);
    foreach ($commandsUsed as $command) {
        $commandBus->addCommand($command);
    }

    $apiMock->setCommandBus($commandBus);
    $apiMock->processCommand($fakeUpdate);

    return $sentMessage;
}

/**
 * Executes a Telegram command as a specific user.
 *
 * @param  User  $user  The user model to execute the command as
 * @param  array  $commandsUsed  Array of all fully-qualified class-names of commands which will be used
 * @param  string  $text  Command text to send (e.g., '/start')
 * @return array{text: string, chat_id: int}|null The message parameters sent by the command, or null if no message was sent
 */
function runCommandAs(User $user, string $text, array $commandsUsed = []): ?array
{
    return runCommand($user->toArray(), $text, $commandsUsed);
}

/**
 * Sends a follow-up message in an active conversation as a specific user.
 *
 * This simulates the user typing a message after starting a conversation
 * (e.g., after /newclass, typing the class name).
 *
 * @param  User  $user  The user model with an active conversation
 * @param  string  $text  The message text to send (no command prefix)
 * @param  array<string, class-string>  $conversationHandlersUsed  Associative array of action => handler class
 *                                                                 Example: ['newclass' => \App\BotCommands\Conversations\NewClassConversation::class]
 * @return array{text: string, chat_id: int}|null The message parameters sent by the conversation handler, or null if no message was sent
 */
function sendConversationMessage(User $user, string $text, array $conversationHandlersUsed = []): ?array
{
    $chat = new Chat([
        'id' => $user->id,
        'first_name' => $user->first_name,
        'type' => 'private',
    ]);
    $updateData = [
        'update_id' => 10002,
        'message' => [
            'message_id' => 1112,
            'from' => $user->toArray(),
            'chat' => $chat,
            'text' => $text,
            'date' => 1234567891,
        ],
    ];

    $fakeUpdate = new Update($updateData);
    $sentMessage = null;

    $apiMock = Mockery::mock(Api::class)->makePartial();
    $apiMock->shouldReceive('sendMessage')
        ->andReturnUsing(function ($params) use (&$sentMessage) {
            $sentMessage = $params;

            return new Message(array_merge(['message_id' => 2], $params));
        });

    $apiMock->shouldReceive('getAccessToken')->andReturn('test:test');

    $handler = new ConversationHandler($apiMock);
    foreach ($conversationHandlersUsed as $c => $h) {
        $handler->registerHandler($c, $h);
    }
    $handler->handle($fakeUpdate);

    return $sentMessage;
}
