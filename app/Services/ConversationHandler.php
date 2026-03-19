<?php

declare(strict_types=1);

namespace App\Services;

use App\BotCommands\Conversations\Conversation;
use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Handles multi-message conversation flows for the Telegram bot.
 *
 * This service routes non-command messages to appropriate conversation
 * handlers based on the user's active conversation state (stored in
 * the users.conversation_state JSON field).
 *
 * ## Conversation state structure:
 * ```php
 * [
 *     'action' => string,  // Registered action identifier
 *     'data'   => array,   // Conversation context data
 * ]
 * ```
 *
 * ## Handler registration:
 * ```php
 * // Using a class name (recommended):
 * $handler->registerHandler('newclass', \App\BotCommands\Conversations\NewClassConversation::class);
 *
 * // Using a callable:
 * $handler->registerHandler('action', function (User $user, string $input, Api $telegram, Update $update) {
 *     // Handle the conversation
 * });
 * ```
 *
 * ## Class-based handlers:
 * Create a class extending \App\BotCommands\Conversations\Conversation:
 * ```php
 * class MyConversation extends \App\BotCommands\Conversations\Conversation
 * {
 *     public function handle(User $user, string $input, Api $telegram, Update $update): void
 *     {
 *         // Process input
 *     }
 * }
 * ```
 */
class ConversationHandler
{
    /** @var array<string, class-string<Conversation>|callable> */
    private array $handlers = [];

    public function __construct(
        private readonly Api $telegram
    ) {}

    /**
     * Register a handler for a conversation action.
     *
     * @param  string  $action  The action identifier (must match user.conversation_state.action)
     * @param  class-string<Conversation>|callable  $handler  Conversation class or callback
     *
     * @example
     * $handler->registerHandler('newclass', NewClassConversation::class);
     * @example
     * $handler->registerHandler('custom', function (User $user, string $input, Api $telegram, Update $update) {
     *     // Handle the conversation
     * });
     */
    public function registerHandler(string $action, string|callable $handler): void
    {
        $this->handlers[$action] = $handler;
    }

    /**
     * Handle an incoming update by checking for active conversations.
     *
     * @param  Update  $update  The Telegram update object
     * @return bool True if a conversation was handled, false otherwise
     */
    public function handle(Update $update): bool
    {
        $message = $update->getMessage();
        if ($message === null) {
            return false;
        }

        $userId = $message->from->id;
        $user = User::find($userId);

        if ($user === null || ! $user->hasActiveConversation()) {
            return false;
        }

        $action = $user->getConversationAction();

        if ($action === null || ! isset($this->handlers[$action])) {
            return false;
        }

        $handler = $this->handlers[$action];

        if (is_string($handler) && is_a($handler, Conversation::class, true)) {
            $conversation = new $handler;
            $conversation->handle($user, $message->text, $this->telegram, $update);
        } else {
            $handler($user, $message->text, $this->telegram, $update);
        }

        return true;
    }

    public function hasHandler(string $action): bool
    {
        return isset($this->handlers[$action]);
    }
}
