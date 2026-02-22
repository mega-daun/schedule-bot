<?php

declare(strict_types=1);

namespace App\Services\CommandHandlers;

use Illuminate\Contracts\Container\Container;

class CommandHandlerFactory
{
    /**
     * Map of command string to handler class.
     *
     * @var array<string, class-string<CommandHandler>>
     */
    private array $map = [
        '/start' => StartCommandHandler::class,
        '/help' => HelpCommandHandler::class,
        '/homework' => HomeworkCommandHandler::class,
        '/schedule' => ScheduleCommandHandler::class,
        '/settings' => SettingsCommandHandler::class,
    ];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Create a command handler for the given command and arguments.
     */
    public function make(string $command, array $arguments): ?CommandHandler
    {
        $handlerClass = $this->map[$command] ?? null;

        if ($handlerClass === null) {
            return null;
        }

        return $this->container->make($handlerClass, [
            'command' => $command,
            'arguments' => $arguments,
        ]);
    }
}
