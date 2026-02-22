<?php

declare(strict_types=1);

namespace App\Services\CommandHandlers;

abstract class CommandHandler
{
    public function __construct(
        protected readonly string $command,
        protected readonly array $arguments,
        protected readonly int|string $chatId,
        /** @param array<string, mixed> Telegram user payload from message.from */
        protected readonly array $from,
    ) {}

    abstract public function handle(): void;
}
