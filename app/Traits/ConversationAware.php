<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Telegram\Bot\Api;

/**
 * Helper trait for bot commands to manage conversation flows.
 *
 * Provides convenience methods for starting, handling, and clearing
 * multi-message conversations with users.
 *
 * Usage in a BotCommand:
 *   use ConversationAware;
 *
 *   // Start a conversation when command needs more input
 *   $this->startConversation($user, 'action_name', ['step' => 1]);
 *
 *   // Get conversation data in a handler
 *   $data = $this->getConversationData($user);
 *
 *   // Clear conversation when done
 *   $this->clearConversation($user);
 *
 *   // Send a reply
 *   $this->reply($telegram, $chatId, 'Your message here');
 */
trait ConversationAware
{
    protected ?User $user = null;

    /**
     * Start a new conversation flow for a user.
     *
     * @param  User  $user  The user to start conversation with
     * @param  string  $action  Action identifier (e.g., 'newclass', 'newhomework')
     * @param  array  $data  Initial context data
     */
    protected function startConversation(User $user, string $action, array $data = []): void
    {
        $user->startConversation($action, $data);
    }

    /**
     * Clear/End the current conversation for a user.
     *
     * @param  User  $user  The user to clear conversation for
     */
    protected function clearConversation(User $user): void
    {
        $user->clearConversationState();
    }

    /**
     * Send a text message to a chat.
     *
     * @param  Api  $telegram  The Telegram API instance
     * @param  int  $chatId  The chat ID to send to
     * @param  string  $text  The message text
     */
    protected function reply(Api $telegram, int $chatId, string $text): void
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * Get the stored context data for a user's conversation.
     *
     * @param  User  $user  The user to get conversation data for
     * @return array The conversation context data
     */
    protected function getConversationData(User $user): array
    {
        return $user->getConversationData();
    }
}
