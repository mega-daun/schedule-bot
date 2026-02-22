<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CommandHandlers\CommandHandlerFactory;
use App\Services\CommandHandlers\HelpCommandHandler;
use App\Services\CommandHandlers\HomeworkCommandHandler;
use App\Services\CommandHandlers\ScheduleCommandHandler;
use App\Services\CommandHandlers\SettingsCommandHandler;
use App\Services\CommandHandlers\StartCommandHandler;
use Tests\TestCase;

class CommandHandlerFactoryTest extends TestCase
{
    public function test_make_returns_start_handler_for_start_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/start', ['arg1']);

        $this->assertInstanceOf(StartCommandHandler::class, $handler);
    }

    public function test_make_returns_help_handler_for_help_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/help', []);

        $this->assertInstanceOf(HelpCommandHandler::class, $handler);
    }

    public function test_make_returns_homework_handler_for_homework_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/homework', []);

        $this->assertInstanceOf(HomeworkCommandHandler::class, $handler);
    }

    public function test_make_returns_schedule_handler_for_schedule_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/schedule', []);

        $this->assertInstanceOf(ScheduleCommandHandler::class, $handler);
    }

    public function test_make_returns_settings_handler_for_settings_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/settings', []);

        $this->assertInstanceOf(SettingsCommandHandler::class, $handler);
    }

    public function test_make_returns_null_for_unknown_command(): void
    {
        $factory = $this->app->make(CommandHandlerFactory::class);

        $handler = $factory->make('/unknown', []);

        $this->assertNull($handler);
    }
}
