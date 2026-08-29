<?php

namespace App\Actions\Class;

use App\Enums\UserRole;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class JoinClassAction
{
    public function __invoke(int $class_id, User $user, UserRole $newRole): void
    {
        if ($user->class_id !== null) {
            throw new InvalidArgumentException(__("error.class.already_member"));
        }

        try {
            $user->update([
                'class_id' => $class_id,
                'role' => $newRole
            ]);
        } catch (QueryException) {
            throw new InvalidArgumentException(__("error.class.not_found"));
        }
    }
}
