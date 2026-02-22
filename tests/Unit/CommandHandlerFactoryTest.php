<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CommandHandlers\ClassCommandHandler;
use App\Services\CommandHandlers\CommandHandlerFactory;
use App\Services\CommandHandlers\HelpCommandHandler;
use App\Services\CommandHandlers\HomeworkCommandHandler;
use App\Services\CommandHandlers\ScheduleCommandHandler;
use App\Services\CommandHandlers\SettingsCommandHandler;
use App\Services\CommandHandlers\StartCommandHandler;
use Tests\TestCase;

class CommandHandlerFactoryTest extends TestCase
{
    private const CHAT_ID = 123456;

    /** @var array<string, mixed> */
    private const FROM = ['id' => 123456, 'first_name' => 'Test'];

    public function test_make_returns_start_handler_for_start_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/start', ['arg1'], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(StartCommandHandler::class, $handler);
    }

    public function test_make_returns_help_handler_for_help_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/help', [], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(HelpCommandHandler::class, $handler);
    }

    public function test_make_returns_homework_handler_for_homework_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/homework', [], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(HomeworkCommandHandler::class, $handler);
    }

    public function test_make_returns_schedule_handler_for_schedule_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/schedule', [], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(ScheduleCommandHandler::class, $handler);
    }

    public function test_make_returns_settings_handler_for_settings_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/settings', [], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(SettingsCommandHandler::class, $handler);
    }

    public function test_make_returns_class_handler_for_class_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/class', ['join', '10Б'], self::CHAT_ID, self::FROM);

        $this->assertInstanceOf(ClassCommandHandler::class, $handler);
    }

    public function test_make_returns_null_for_unknown_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/unknown', [], self::CHAT_ID, self::FROM);

        $this->assertNull($handler);
    }
}
