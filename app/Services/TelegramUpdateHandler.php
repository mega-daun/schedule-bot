<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Handle a single Telegram update payload.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        // For now, just log the incoming update so we can
        // verify that long polling works during development.
        $this->logger->info('Received Telegram update', [
            'update' => $update,
        ]);

        // TODO: Dispatch to domain-specific handlers and services
        // (e.g. homework, events, notifications) as they are implemented.
    }
}

