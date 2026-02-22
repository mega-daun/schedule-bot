<?php

declare(strict_types=1);

namespace App\Services\CommandHandlers;

class StartCommandHandler extends CommandHandler
{
    public function __construct(string $command, array $arguments)
    {
        parent::__construct($command, $arguments);
    }

    public function handle(): void
    {
        // TODO: Implement /start command handling logic.
    }
}
