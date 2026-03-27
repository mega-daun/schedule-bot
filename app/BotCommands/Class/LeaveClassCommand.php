<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\BaseCommand;
use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;

class LeaveClassCommand extends BaseCommand
{
    protected string $name = 'leaveclass';

    protected string $description = 'Покинуть текущий класс.';

    protected function __getArgs(): array
    {
        return [];
    }

    protected function __handle(array $args): void
    {
        if ($this->user->class_id === null) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        $classroom = Classroom::find($this->user->class_id);
        $classCode = $classroom->code;

        if ($this->user->role === UserRole::Admin) {
            $this->handleAdminLeaving($classroom);
        } else {
            $this->handleUserLeaving();
        }

        $this->replyWithMessage([
            'text' => 'Вы вышли из класса '.$classCode.'.',
        ]);
    }

    private function handleUserLeaving(): void
    {
        $this->user->update([
            'class_id' => null,
            'role' => UserRole::Student,
            'conversation_state' => null,
        ]);
    }

    private function handleAdminLeaving(Classroom $classroom): void
    {
        $otherUsers = $classroom->users()->where('id', '!=', $this->user->id)->get();

        if ($otherUsers->isEmpty()) {
            $classroom->delete();
        } else {
            $otherUsers->random()->update(['role' => UserRole::Admin]);
        }

        $this->user->update([
            'class_id' => null,
            'role' => UserRole::Student,
            'conversation_state' => null,
        ]);
    }
}
