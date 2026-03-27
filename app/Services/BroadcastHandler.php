<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Telegram\Bot\Api;

class BroadcastHandler
{
    public function __construct(private readonly Api $telegram) {}

    public function broadcastTo(Collection $users, string $text): void
    {
        $users->map(
            function (User $user) use ($text) {
                $this->telegram->sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => $text,
                ]);
            }
        );
    }
}
