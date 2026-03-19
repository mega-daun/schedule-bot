<?php

declare(strict_types=1);

use App\Models\User;
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
                    'length' => 6,
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
