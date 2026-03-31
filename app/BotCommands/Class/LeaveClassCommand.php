<?php

declare(strict_types=1);

namespace App\BotCommands\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class LeaveClassCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class_id === null) {
            throw new IncorrectMessageException('Вы не состоите в классе.');
        }

        $classroom = Classroom::find($user->class_id);
        $classCode = $classroom->code;

        if ($user->role === UserRole::Admin) {
            $this->handleAdminLeaving($user, $classroom);
        } else {
            $this->handleUserLeaving($user);
        }

        $bot->sendMessage(
            text: 'Вы вышли из класса '.$classCode.'.'
        );
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function handleUserLeaving(User $user): void
    {
        $user->update([
            'class_id' => null,
            'role' => UserRole::Student,
        ]);
    }

    private function handleAdminLeaving(User $user, Classroom $classroom): void
    {
        $otherUsers = $classroom->users()->where('id', '!=', $user->id)->get();

        if ($otherUsers->isEmpty()) {
            $classroom->delete();
        } else {
            $otherUsers->random()->update(['role' => UserRole::Admin]);
        }

        $user->update([
            'class_id' => null,
            'role' => UserRole::Student,
        ]);
    }
}
