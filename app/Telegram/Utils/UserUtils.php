<?php

declare(strict_types=1);

namespace App\Telegram\Utils;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SergiX44\Nutgram\Nutgram;

trait UserUtils
{
    /**
     * Find existing user by Telegram ID or create a new one.
     *
     * Used when the conversation should auto-register unknown users
     * (e.g. /start command). For conversations that require the user
     * to already exist, use {@see getUser()} instead.
     */
    protected function getOrAddUser(Nutgram $bot, array $with = []): User
    {
        $telegramUser = $bot->user();

        try {
            return User::with($with)->findOrFail($telegramUser->id);
        } catch (ModelNotFoundException) {
            return User::create([
                'id' => $telegramUser->id,
                'first_name' => $telegramUser->first_name,
                'language_code' => $telegramUser?->language_code,
                'username' => $telegramUser?->username,
            ]);
        }
    }

    /**
     * Find user by Telegram ID or throw ModelNotFoundException.
     *
     * Use this in conversations that require the user to already
     * exist in the database (e.g. class member checks).
     *
     * @throws ModelNotFoundException
     */
    protected function getUser(Nutgram $bot, array $with = []): User
    {
        return User::with($with)->findOrFail($bot->user()->id);
    }
}
