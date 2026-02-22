<?php

namespace App\Services\CommandHandlers;

abstract class CommandHandler
{
    private string $command;

    private array $arguments;

    public function __construct(string $command, array $arguments)
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    // All stuff happens here
    abstract public function handle(): void;
}
