<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\CommandHandlers\CommandHandlerFactory;
use Psr\Log\LoggerInterface;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CommandHandlerFactory $commandHandlerFactory,
    ) {}

    public function validateMessage(string $messageText): bool
    {
        if (! is_string($messageText)) {
            $this->logger->debug('Update does not contain a text message, skipping command handling.');

            return false;
        }

        $messageText = trim($messageText);

        if ($messageText === '' || $messageText[0] !== '/') {
            $this->logger->debug('Message is not a command, skipping command handling.', [
                'text' => $messageText,
            ]);

            return false;
        }

        return true;
    }

    public function parseMessage(string $messageText): array
    {
        $parts = preg_split('/\s+/', $messageText);

        if ($parts === false || count($parts) === 0) {
            $this->logger->warning('Failed to parse command from message text.', [
                'text' => $messageText,
            ]);

            return [];
        }

        $command = $parts[0];
        $arguments = array_slice($parts, 1);

        $this->logger->info('Parsed Telegram command', [
            'command' => $command,
            'arguments' => $arguments,
        ]);

        return [
            $command,
            $arguments,
        ];
    }

    /**
     * Handle a single Telegram update payload.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $this->logger->info('Received Telegram update', [
            'update' => $update,
        ]);

        $messageText = $update['message']['text'] ?? null;

        if (! $this->validateMessage($messageText)) {
            return;
        }

        [$command, $arguments] = $this->parseMessage($messageText);

        $chatId = $update['message']['chat']['id'] ?? null;
        $from = $update['message']['from'] ?? [];

        if ($chatId === null || $from === []) {
            $this->logger->warning('Update missing chat_id or from, skipping command handling.');

            return;
        }

        $handler = $this->commandHandlerFactory->make($command, $arguments, $chatId, $from);

        if ($handler === null) {
            $this->logger->warning('Received unknown command', [
                'command' => $command,
            ]);

            return;
        }

        try {
            $handler->handle();
        } catch (\Throwable $exception) {
            $this->logger->error('Error while handling Telegram command', [
                'command' => $command,
                'arguments' => $arguments,
                'exception' => $exception,
            ]);
        }
    }
}
