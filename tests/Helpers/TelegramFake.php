<?php

declare(strict_types=1);

use App\BotCommands\CancelCommand;
use App\BotCommands\Class\ChangeRoleCommand;
use App\BotCommands\Class\DeleteClassCommand;
use App\BotCommands\Class\JoinClassCommand;
use App\BotCommands\Class\LeaveClassCommand;
use App\BotCommands\Class\NewClassCommand;
use App\BotCommands\Conversations\JoinClassConversation;
use App\BotCommands\Conversations\NewClassConversation;
use App\BotCommands\StartCommand;
use App\Http\Middleware\IncorrectMessageMiddleware;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;

function createTestBot(): FakeNutgram
{
    $bot = Nutgram::fake();

    $bot->middleware(IncorrectMessageMiddleware::class);

    $bot->onCommand('start', new StartCommand);
    $bot->onCommand('start {token}', new StartCommand);
    $bot->onCommand('cancel', new CancelCommand);

    $bot->onCommand('newclass', new NewClassConversation);
    $bot->onCommand('newclass {code}', new NewClassCommand);

    $bot->onCommand('joinclass', new JoinClassConversation);
    $bot->onCommand('joinclass {token}', new JoinClassCommand);

    $bot->onCommand('deleteclass', new DeleteClassCommand);
    $bot->onCommand('leaveclass', new LeaveClassCommand);
    $bot->onCommand('changerole', new ChangeRoleCommand);
    $bot->onCommand('changerole {username} {role}', new ChangeRoleCommand);

    return $bot;
}

function runCommand(array $from, string $text, array $commandsUsed = []): ?array
{
    $bot = createTestBot();

    $bot->hearMessage([
        'message_id' => 1111,
        'date' => 1234567890,
        'chat' => [
            'id' => $from['id'],
            'first_name' => $from['first_name'],
            'type' => 'private',
        ],
        'from' => $from,
        'text' => $text,
    ])->reply();

    try {
        $bot->assertReply('sendMessage');
        $history = $bot->getRequestHistory();
        if (! empty($history)) {
            $lastRequest = end($history);
            $response = $lastRequest['response'] ?? null;

            if ($response && method_exists($response, 'getBody')) {
                $body = json_decode((string) $response->getBody(), true);
                if (isset($body['result']['text'])) {
                    return [
                        'text' => $body['result']['text'],
                        'chat_id' => $body['result']['chat_id'] ?? $from['id'],
                    ];
                }
            }
        }
    } catch (Exception $e) {
        return null;
    }

    return null;
}

function runCommandAs(User $user, string $text, array $commandsUsed = []): ?array
{
    return runCommand($user->toArray(), $text, $commandsUsed);
}

function sendConversationMessage(User $user, string $text, array $conversationHandlersUsed = []): ?array
{
    $bot = createTestBot();

    $bot->hearMessage([
        'message_id' => 1112,
        'date' => 1234567891,
        'chat' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'type' => 'private',
        ],
        'from' => $user->toArray(),
        'text' => $text,
    ])->reply();

    try {
        $bot->assertReply('sendMessage');
        $history = $bot->getRequestHistory();
        if (! empty($history)) {
            $lastRequest = end($history);
            $response = $lastRequest['response'] ?? null;

            if ($response && method_exists($response, 'getBody')) {
                $body = json_decode((string) $response->getBody(), true);
                if (isset($body['result']['text'])) {
                    return [
                        'text' => $body['result']['text'],
                        'chat_id' => $body['result']['chat_id'] ?? $user->id,
                    ];
                }
            }
        }
    } catch (Exception $e) {
        return null;
    }

    return null;
}
