<?php

declare(strict_types=1);

namespace App\Actions\Class;

use App\Exceptions\UnknownRoleException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ChangeRoleAction
{
    public function getClassMembers(int $classId, int $excludeUserId): Collection
    {
        return User::where('class_id', $classId)
            ->where('id', '!=', $excludeUserId)
            ->get();
    }

    public function findUser(int $userId): User
    {
        $user = User::find($userId);

        if ($user === null) {
            throw new InvalidArgumentException(__('error.role.user_not_found'));
        }

        return $user;
    }

    public function changeRole(User $targetUser, string $role): void
    {
        try {
            $targetUser->changeRole($role);
        } catch (UnknownRoleException) {
            throw new InvalidArgumentException(__('error.role.invalid'));
        }
    }
}
