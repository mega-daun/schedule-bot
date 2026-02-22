<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CommandHandlers\CommandHandler;
use App\Services\CommandHandlers\CommandHandlerFactory;
use App\Services\TelegramUpdateHandler;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class TelegramUpdateHandlerTest extends TestCase
{
    public function test_handle_delegates_to_factory_handler(): void
    {
        $handler = $this->createMock(CommandHandler::class);
        $handler->expects($this->once())
            ->method('handle');

        $factory = $this->createMock(CommandHandlerFactory::class);
        $factory->method('make')
            ->with('/start', [], 123456, ['id' => 123456, 'first_name' => 'Test'])
            ->willReturn($handler);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info');
        $logger->method('debug');

        $updateHandler = new TelegramUpdateHandler($logger, $factory);

        $updateHandler->handle([
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 123456],
                'from' => ['id' => 123456, 'first_name' => 'Test'],
            ],
        ]);
    }

    public function test_handle_logs_warning_for_unknown_command(): void
    {
        $factory = $this->createMock(CommandHandlerFactory::class);
        $factory->method('make')
            ->with('/unknown', [], 123456, ['id' => 123456, 'first_name' => 'Test'])
            ->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with('Received unknown command', ['command' => '/unknown']);

        $updateHandler = new TelegramUpdateHandler($logger, $factory);

        $updateHandler->handle([
            'message' => [
                'text' => '/unknown',
                'chat' => ['id' => 123456],
                'from' => ['id' => 123456, 'first_name' => 'Test'],
            ],
        ]);
    }

    public function test_handle_logs_error_when_handler_throws(): void
    {
        $handler = $this->createMock(CommandHandler::class);
        $handler->method('handle')
            ->willThrowException(new \RuntimeException('Handler error'));

        $factory = $this->createMock(CommandHandlerFactory::class);
        $factory->method('make')
            ->willReturn($handler);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info');
        $logger->method('debug');
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Error while handling Telegram command',
                $this->callback(function (array $context): bool {
                    return $context['command'] === '/start'
                        && $context['exception'] instanceof \RuntimeException;
                })
            );

        $updateHandler = new TelegramUpdateHandler($logger, $factory);

        $updateHandler->handle([
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 123456],
                'from' => ['id' => 123456, 'first_name' => 'Test'],
            ],
        ]);
    }
}
