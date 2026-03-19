<?php

namespace App\BotCommands\\srv\http\schedule-bot\app\BotCommands\\HomeworkEditHomeworkCommand.php;

use App\BotCommands\BaseCommand;
use App\Traits\HasClass;
use App\Traits\HasUser;

class HomeworkEditHomeworkCommand extends BaseCommand
{
    protected string $name = "";

    protected string $description = "";

    protected string $pattern = "";

    protected function __getArgs(): array
    {
        return [
            // ...
        ];
    }

    protected function __handle(array $args): mixed
    {
        // ...
    }
}
