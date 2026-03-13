<?php

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Traits\HasClass;
use App\Traits\HasUser;

class NewClassCommand extends BaseCommand
{
    use HasClass, HasUser;

    protected string $name = 'newclass';

    protected string $description = 'Создать новый класс. Пример: /newclass 10Б';

    protected string $pattern = '{code}';

    protected function __getArgs(): array
    {
        return [
            'code' => $this->argument('code'),
        ];
    }

    protected function __handle(array $args): void
    {
        $code = $args['code'];
        $this->setUser($this->getUpdate()->getMessage()->from);

        if ($code === null) {
            $this->replyWithMessage([
                'text' => 'Для создания класса, укажите название.',
            ]);

            return;
        }

        if ($this->user->class !== null) {
            $this->replyWithMessage([
                'text' => 'Вы уже состоите в классе.',
            ]);

            return;
        }

        $this->class = Classroom::create([
            'code' => $code,
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $this->class) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка при создании класса.',
            ]);

            return;
        }

        if (! $this->user->update(['class_id' => $this->class->id, 'role' => UserRole::Admin])) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка при присоединении к классу.',
            ]);

            return;
        }

        $this->replyWithMessage([
            'text' => 'Вы успешно создали класс '.$this->class->code.'. Токен для присоединения: '.$this->class->join_token.'. Ссылка для присоединения: https://t.me/'.env('TELEGRAM_BOT_NAME', 'hatenigas_bot').'?start='.$this->class->join_token,
        ]);
    }
}
