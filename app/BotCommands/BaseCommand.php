<?php

declare(strict_types=1);

namespace App\BotCommands;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Telegram\Bot\Commands\Command;

/**
 * Abstract base command providing centralized error handling
 * for all Telegram bot commands. Subclasses must implement:
 * - __getArgs() to extract arguments from Telegram update
 * - __handle(array $args) to contain the core command logic
 */
abstract class BaseCommand extends Command
{
    /**
     * Extract arguments from Telegram update.
     * Subclasses must implement specific argument parsing.
     *
     * @return array Command arguments
     */
    abstract protected function __getArgs(): array;

    /**
     * Execute command logic with provided arguments.
     *
     * @param  array  $args  Command arguments
     * @return mixed Command execution result
     */
    abstract protected function __handle(array $args): mixed;

    /**
     * Execute command with error handling and validation.
     */
    public function handle(): void
    {
        try {
            $args = $this->__getArgs();

            if (! is_array($args) || empty($args)) {
                throw new InvalidArgumentException('Missing command arguments');
            }

            $this->__handle($args);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle error by logging and replying to user.
     */
    protected function handleError(\Exception $e): void
    {
        Log::error(
            'Command '.$this->name.' failed: '.$e->getMessage(),
            [
                'command' => $this->name,
                'args' => $args ?? [],
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]
        );

        $this->replyWithMessage([
            'text' => 'При выполнении команды произошла ошибка на стороне сервера. Попробуйте позже.',
        ]);
    }
}
