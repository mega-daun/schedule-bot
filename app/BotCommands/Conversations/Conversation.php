<?php

declare(strict_types=1);

namespace App\BotCommands\Conversations;

use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Base class for conversation handlers.
 *
 * Extend this class to create multi-message conversation flows.
 *
 * Usage:
 * ```php
 * class NewClassConversation extends Conversation
 * {
 *     public function handle(User $user, string $input, Api $telegram, Update $update): void
 *     {
 *         // Process input and complete the conversation
 *     }
 * }
 * ```
 */
abstract class Conversation
{
    /**
     * Handle the user's input for this conversation step.
     *
     * @param  User  $user  The user continuing the conversation
     * @param  string  $input  The user's message input
     * @param  Api  $telegram  The Telegram API instance
     * @param  Update  $update  The full Telegram update object
     */
    abstract public function handle(User $user, string $input, Api $telegram, Update $update): void;
}
