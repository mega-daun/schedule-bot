<?php

declare(strict_types=1);

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

    protected string $description = 'Создать новый класс. Пример: /newclass 10Б или /newclass (для пошагового создания)';

    protected string $pattern = '{code}';

    protected function __getArgs(): array
    {
        return [
            'code' => $this->argument('code'),
        ];
    }

    protected function __handle(array $args): mixed
    {
        $code = $args['code'];
        $this->setUser($this->getUpdate()->getMessage()->from);

        if ($this->user->class !== null) {
            $this->replyWithMessage([
                'text' => 'Вы уже состоите в классе.',
            ]);

            return null;
        }

        if ($code === null) {
            $this->user->startConversation('newclass', []);

            $this->replyWithMessage([
                'text' => 'Введите название нового класса (например, 10Б):',
            ]);

            return null;
        }

        return $this->createClass($code);
    }

    private function createClass(string $code): mixed
    {
        $this->class = Classroom::create([
            'code' => $code,
            'join_token' => Classroom::generateJoinToken(),
        ]);

        if (! $this->class) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка при создании класса.',
            ]);

            return null;
        }

        if (! $this->user->update(['class_id' => $this->class->id, 'role' => UserRole::Admin])) {
            $this->replyWithMessage([
                'text' => 'Произошла ошибка при присоединении к классу.',
            ]);

            return null;
        }

        $this->replyWithMessage([
            'text' => 'Класс '.$this->class->code.' успешно создан. Токен для присоединения: '.$this->class->join_token.'. Ссылка для присоединения: https://t.me/'.env('TELEGRAM_BOT_NAME', 'hatenigas_bot').'?start='.$this->class->join_token,
        ]);

        return null;
    }
}
