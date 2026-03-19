<?php

declare(strict_types=1);

namespace App\Providers;

use App\BotCommands\Conversations\NewClassConversation;
use App\Services\ConversationHandler;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

/**
 * Service provider for Telegram bot services.
 *
 * Registers the ConversationHandler singleton and all conversation action handlers.
 *
 * ## Available Conversation Actions
 *
 * | Action | Class | Description |
 * |--------|-------|-------------|
 * | `'newclass'` | NewClassConversation | Handles multi-step class creation |
 *
 * ## Adding a new conversation:
 * 1. Create a class extending \App\BotCommands\Conversations\Conversation
 * 2. Register it in registerConversationHandlers():
 *    $handler->registerHandler('action_name', YourConversationClass::class);
 */
class BotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConversationHandler::class, function ($app) {
            $telegram = TelegramFacade::bot();
            $handler = new ConversationHandler($telegram);

            $this->registerConversationHandlers($handler);

            return $handler;
        });
    }

    /**
     * Register all conversation handlers.
     */
    private function registerConversationHandlers(ConversationHandler $handler): void
    {
        $handler->registerHandler('newclass', NewClassConversation::class);
    }
}
