<?php

declare(strict_types=1);

namespace App\BotCommands;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Traits\Attributes\Setup;
use App\Traits\HasClass;
use App\Traits\HasUser;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Telegram\Bot\Commands\Command;

/**
 * Abstract base command providing centralized error handling
 * for all Telegram bot commands. Subclasses must implement:
 * - __getArgs() to extract arguments from Telegram update
 * - __handle(array $args) to contain the core command logic
 */
abstract class BaseCommand extends Command
{
    use HasClass, HasUser;

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
     */
    abstract protected function __handle(array $args): void;

    /**
     * Execute command with error handling and validation.
     */
    public function handle(): void
    {
        try {
            $this->setup();
            $args = $this->__getArgs();

            $this->__handle($args);
        } catch (IncorrectMessageException $e) {
            if ($e->shouldClearConversation() && $this->user) {
                $this->user->clearConversationState();
            }
            $this->replyWithMessage([
                'text' => $e->getMessage(),
            ]);

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

    private function setup(): void
    {
        $reflection = new ReflectionClass($this);

        $setupMethods = $this->getSetupMethods($reflection);

        $this->sortSetupMethodsByOrder($setupMethods);

        foreach ($setupMethods as $method) {
            $method->invoke($this, $this->getUpdate());
        }
    }

    private function getSetupMethods(ReflectionClass $reflection): array
    {
        return array_filter(
            $reflection->getMethods(),
            fn ($method) => ! empty($method->getAttributes(Setup::class))
        );
    }

    private function sortSetupMethodsByOrder(iterable &$setupMethods): void
    {
        usort(
            $setupMethods,
            fn ($a, $b) => $a->getAttributes(Setup::class)[0]->newInstance()->order <=> $b->getAttributes(Setup::class)[0]->newInstance()->order
        );
    }
}
