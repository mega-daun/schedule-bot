<?php

namespace App\Telegram\Conversations;

use App\Exceptions\IncorrectMessageException;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Base conversation class providing shared utilities for all bot conversations.
 *
 * Consolidates common patterns: user retrieval, callback data parsing,
 * error handling, and message sending with conversation flow control.
 */
class BaseConversation extends Conversation
{
    /**
     * Find existing user by Telegram ID or create a new one.
     *
     * Used when the conversation should auto-register unknown users
     * (e.g. /start command). For conversations that require the user
     * to already exist, use {@see getUser()} instead.
     */
    protected function getOrAddUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        try {
            return User::findOrFail($telegramUser->id);
        } catch (ModelNotFoundException) {
            return User::create([
                'id' => $telegramUser->id,
                'first_name' => $telegramUser->first_name,
                'language_code' => $telegramUser?->language_code,
                'username' => $telegramUser?->username,
            ]);
        }
    }

    /**
     * Find user by Telegram ID or throw ModelNotFoundException.
     *
     * Use this in conversations that require the user to already
     * exist in the database (e.g. class member checks).
     *
     * @throws ModelNotFoundException
     */
    protected function getUser(Nutgram $bot): User
    {
        return User::findOrFail($bot->user()->id);
    }

    /**
     * Validate that the incoming update is a callback query with the
     * expected prefix, then return the callback-specific part.
     *
     * Returns the portion of callback data after the prefix and dot separator,
     * or false if the update is not a callback query, the prefix doesn't match,
     * or the callback data equals the prefix with no suffix.
     *
     * Example: getCallbackAnswer($bot, 'newschedule.weekday')
     *   on callback data 'newschedule.weekday.add.1' returns 'add.1'
     *
     * @return string|bool The callback-specific suffix, or false on failure
     */
    protected function getCallbackAnswer(Nutgram $bot, string $prefix): string|bool
    {
        if (! $bot->isCallbackQuery()) {
            return false;
        }
        $data = $bot->callbackQuery()->data;

        if (! str_starts_with($data, $prefix) || strlen($data) === strlen($prefix)) {
            return false;
        }

        return substr($data, strlen($prefix) + 1);
    }

    /**
     * Same as {@see getCallbackAnswer()}, but sends an error message and
     * redirects to the specified step on failure instead of returning false.
     *
     * Intended for use in conversation step methods where invalid input
     * should prompt the user and repeat the current step.
     *
     * @param  string  $prefix  Expected callback data prefix (e.g. 'newschedule.weekday')
     * @param  string  $nextStepOnError  Conversation step method to resume on failure
     * @param  string|null  $errorMessage  Error message to send the user, defaults to click_button prompt
     * @return string|bool The callback-specific suffix, or false on failure (after error is thrown)
     */
    protected function getCallbackAnswerOrError(Nutgram $bot, string $prefix, string $nextStepOnError, ?string $errorMessage = null): string|bool
    {
        $result = $this->getCallbackAnswer($bot, $prefix);
        if ($result === false) {
            $this->errorAndProceed($errorMessage ?? __('prompt.general.click_button'), $nextStepOnError);

            return false;
        }

        return $result;
    }

    /**
     * Send an error message to the user and continue the conversation
     * at the specified step.
     *
     * Sets the next step before throwing, so the conversation resumes
     * at the correct step after the middleware handles the exception.
     *
     * @param  string  $message  Error message to send
     * @param  string  $nextStep  Conversation step method to resume
     *
     * @throws IncorrectMessageException always, caught by IncorrectMessageMiddleware
     */
    protected function errorAndProceed(string $message, string $nextStep): void
    {
        $this->next($nextStep);
        throw new IncorrectMessageException(replyMessage: $message, clearConversation: false);
    }

    /**
     * Send an error message and terminate the conversation.
     *
     * @param  string  $message  Error message to send
     *
     * @throws IncorrectMessageException always, caught by IncorrectMessageMiddleware
     */
    protected function errorAndEnd(string $message): void
    {
        throw new IncorrectMessageException(replyMessage: $message, clearConversation: true);
    }

    /**
     * Send a message to the user and advance the conversation to the next step.
     *
     * Convenience wrapper combining sendMessage() + next().
     *
     * @param  string  $message  Text to send
     * @param  string  $nextStep  Conversation step method to invoke on next update
     * @param  InlineKeyboardMarkup|null  $keyboard  Optional inline keyboard to attach
     */
    protected function replyAndProceed(Nutgram $bot, string $message, string $nextStep, ?InlineKeyboardMarkup $keyboard = null): void
    {
        $bot->sendMessage(text: $message, reply_markup: $keyboard);
        $this->next($nextStep);
    }

    /**
     * Send a message and end the conversation.
     *
     * Convenience wrapper combining sendMessage() + end().
     *
     * @param  string  $message  Text to send
     * @param  InlineKeyboardMarkup|null  $keyboard  Optional inline keyboard to attach
     */
    protected function replyAndEnd(Nutgram $bot, string $message, ?InlineKeyboardMarkup $keyboard = null): void
    {
        $bot->sendMessage(text: $message, reply_markup: $keyboard);
        $this->end();
    }
}
