<?php

namespace App\BotCommands;

use App\Custom\Keyboards\KeyboardButtonRequestUsers;

class TestCommand extends BaseCommand
{
    protected string $name = 'test';

    protected string $description = '';

    protected string $pattern = '';

    private function __getArgs()
    {
        return [];
    }

    private function __handle(array $args)
    {
        $this->replyWithMessage([
            'Тест клавиатуры выбора пользователя',
            'reply_markup' => KeyboardButtonRequestUsers::createChangeUserSelection('Выберите пользователя'),
        ]);
    }
}
