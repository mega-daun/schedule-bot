<?php

namespace App\Actions\Class;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class JoinClassAction
{
    private const string JOIN_TOKEN_FORMAT = '/^[a-f0-9]{16}$/i';

    public function __invoke(int $class_id, User $user, UserRole $newRole = UserRole::Admin): void
    {
        if ($user->class_id !== null) {
            throw new InvalidArgumentException(__('error.class.already_member'));
        }

        try {
            $user->update([
                'class_id' => $class_id,
                'role' => $newRole,
            ]);
        } catch (QueryException) {
            throw new InvalidArgumentException(__('error.class.not_found'));
        }
    }

    public function byToken(string $token, User $user, UserRole $newRole = UserRole::Student): void
    {
        $token = trim($token);
        if (strlen($token) == 0) {
            throw new InvalidArgumentException(__('prompt.class.token_empty'));
        }
        if (preg_match(self::JOIN_TOKEN_FORMAT, $token) !== 1) {
            throw new InvalidArgumentException(__('prompt.class.token_invalid'));
        }

        $class = Classroom::where('join_token', $token)->first(['id']);
        if ($class === null) {
            throw new InvalidArgumentException(__('error.class.not_found_with_token'));
        }

        ($this)($class->id, $user, $newRole);
    }
}
