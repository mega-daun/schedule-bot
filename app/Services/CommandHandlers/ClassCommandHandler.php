<?php

declare(strict_types=1);

namespace App\Services\CommandHandlers;

use App\Jobs\SendTelegramMessage;
use App\Models\Classroom;
use App\Models\User;

class ClassCommandHandler extends CommandHandler
{
    /**
     * Max length for class code (matches classes.code column).
     */
    private const MAX_CLASS_CODE_LENGTH = 5;

    public function handle(): void
    {
        $user = User::findOrCreate($this->from);

        $subcommand = $this->arguments[0] ?? null;

        if ($subcommand === 'join') {
            $this->handleJoin($user);

            return;
        }

        if ($subcommand === 'leave') {
            $this->handleLeave($user);

            return;
        }

        SendTelegramMessage::dispatch(
            $this->chatId,
            'Usage: `/class join {class_name}` or `/class leave`'
        );
    }

    private function handleJoin(User $user): void
    {
        $classCode = $this->arguments[1] ?? null;

        if ($classCode === null || $classCode === '') {
            SendTelegramMessage::dispatch(
                $this->chatId,
                'Please specify a class name. Example: `/class join 10Б`'
            );

            return;
        }

        $classCode = trim($classCode);

        if (! preg_match('/^[0-9]{1,2}[a-zA-ZА-Яа-яЁё]$/u', $classCode)) {
            SendTelegramMessage::dispatch(
                $this->chatId,
                'Class name can only contain letters and numbers.'
            );

            return;
        }

        $classroom = Classroom::firstOrCreate([
            'code' => $classCode
        ]);
        $user->class_id = $classroom->id;
        $user->save();

        SendTelegramMessage::dispatch(
            $this->chatId,
            "You have joined class {$classroom->code}."
        );
    }

    private function handleLeave(User $user): void
    {
        if ($user->class_id === null) {
            SendTelegramMessage::dispatch(
                $this->chatId,
                'You are not in any class.'
            );

            return;
        }

        $user->class_id = null;
        $user->save();

        SendTelegramMessage::dispatch(
            $this->chatId,
            'You have left your class.'
        );
    }
}
