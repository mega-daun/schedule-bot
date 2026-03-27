<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use App\Traits\Attributes\Setup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Telegram\Bot\Objects\Update;

/**
 * @property User|null $user
 *
 * @method void setUser(\Telegram\Bot\Objects\User $from)
 */
trait HasUser
{
    /**
     * related user
     */
    protected ?User $user;

    /**
     * @return void
     */
    #[Setup(order: 1)]
    protected function setUser(Update $update)
    {
        $from = $update->getMessage()->from;
        try {
            $this->user = User::findOrFail(['id' => $from->id])->first();
        } catch (ModelNotFoundException $e) {
            $this->user = User::create([
                'id' => $from->id,
                'first_name' => $from->firstName,
                'language_code' => $from->languageCode,
                'username' => $from->username,
            ]);
        }
    }
}
