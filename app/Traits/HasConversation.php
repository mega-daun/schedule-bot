<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Provides conversation state management capabilities for models.
 *
 * This trait manages multi-message conversation flows by storing the current
 * conversation state in the `conversation_state` database column.
 *
 * conversation_state structure:
 * ```php
 * [
 *     'action' => string,      // Command/action identifier (e.g., 'newclass', 'newhomework')
 *     'data'   => array|null, // Additional context for the conversation
 * ]
 * ```
 *
 * Usage:
 * ```php
 * // Start a conversation
 * $user->startConversation('newclass', ['step' => 1]);
 *
 * // Check if user is in a conversation
 * if ($user->hasActiveConversation()) {
 *     $action = $user->getConversationAction();
 *     $data = $user->getConversationData();
 * }
 *
 * // Update conversation data
 * $user->updateConversationData(['class_code' => '10B']);
 *
 * // End conversation
 * $user->clearConversationState();
 * ```
 *
 * Available conversation actions:
 * - 'newclass': User is creating a new class (awaiting class name input)
 *
 * @mixin Model
 */
trait HasConversation
{
    /**
     * Check if user has an active conversation in progress.
     *
     * @return bool True if conversation_state is not null
     */
    public function hasActiveConversation(): bool
    {
        return $this->conversation_state !== null;
    }

    /**
     * Start a new conversation flow for this user.
     *
     * Stores the action identifier and optional context data in the
     * conversation_state column. Subsequent messages from this user
     * will be routed to the appropriate conversation handler.
     *
     * @param  string  $action  The conversation action identifier (e.g., 'newclass', 'newhomework')
     * @param  array  $data  Initial context data for the conversation (e.g., ['step' => 1])
     *
     * @example
     * $user->startConversation('newclass', []);
     * // Sets: ['action' => 'newclass', 'data' => []]
     * @example
     * $user->startConversation('newhomework', ['subject_id' => 5]);
     * // Sets: ['action' => 'newhomework', 'data' => ['subject_id' => 5]]
     */
    public function startConversation(string $action, array $data = []): void
    {
        $this->update([
            'conversation_state' => [
                'action' => $action,
                'data' => $data,
            ],
        ]);
    }

    /**
     * Clear/End the current conversation for this user.
     *
     * Sets conversation_state to null, effectively ending any
     * active multi-message conversation flow.
     *
     *
     * @example
     * $user->clearConversationState();
     */
    public function clearConversationState(): void
    {
        $this->update(['conversation_state' => null]);
    }

    /**
     * Get the current conversation action identifier.
     *
     * @return string|null The action string (e.g., 'newclass') or null if no active conversation
     *
     * @example
     * $action = $user->getConversationAction();
     * // Returns: 'newclass' or null
     */
    public function getConversationAction(): ?string
    {
        return $this->conversation_state['action'] ?? null;
    }

    /**
     * Get the stored context data for the current conversation.
     *
     * Returns the 'data' portion of the conversation_state, which
     * contains any additional context accumulated during the conversation.
     *
     * @return array The data array or empty array if no conversation
     *
     * @example
     * $data = $user->getConversationData();
     * // Returns: ['step' => 1, 'class_code' => '10B'] or []
     */
    public function getConversationData(): array
    {
        return $this->conversation_state['data'] ?? [];
    }

    /**
     * Merge new data into the existing conversation context.
     *
     * Updates the 'data' portion of conversation_state by merging
     * the provided array with existing data.
     *
     * @param  array  $data  Data to merge into existing context
     *
     * @example
     * // If current state is: ['action' => 'newhomework', 'data' => ['subject_id' => 5]]
     * $user->updateConversationData(['title' => 'Math homework']);
     * // State becomes: ['action' => 'newhomework', 'data' => ['subject_id' => 5, 'title' => 'Math homework']]
     */
    public function updateConversationData(array $data): void
    {
        if ($this->conversation_state === null) {
            throw new Exception('Trying to update empty conversation_state');
        }

        $this->update([
            'conversation_state' => [
                'action' => $this->conversation_state['action'],
                'data' => array_merge($this->conversation_state['data'] ?? [], $data),
            ],
        ]);
    }
}
