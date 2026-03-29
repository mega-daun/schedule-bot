<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use App\Traits\Attributes\Setup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User as ObjectsUser;

/**
 * @property User|null $user
 *
 * @method void setUser(\Telegram\Bot\Objects\User $from)
 */
trait HasUser
{
    protected ?User $user;

    #[Setup(order: 1)]
    protected function setUser(Update $update)
    {
        $user = $update->getMessage()->from;
        try {
            $this->user = $this->tryToFindUser($user);
        } catch (ModelNotFoundException) {
            $this->user = $this->createUser($user);
        }
    }

    private function tryToFindUser(ObjectsUser $user)
    {
        return User::findOrFail(['id' => $user->id])->first();
    }

    private function createUser(ObjectsUser $user)
    {
        return User::create([
            'id' => $user->id,
            'first_name' => $user->firstName,
            'language_code' => $user->languageCode,
            'username' => $user->username,
        ]);
    }
}
