<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use SergiX44\Nutgram\Nutgram;

class BroadcastToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Collection $userIds,
        private readonly string $message,
    ) {}

    public function handle(Nutgram $bot): void
    {
        $users = User::whereIn('id', $this->userIds)->get();
        $chatIds = $users->pluck('id')->toArray();

        $bot->getBulkMessenger()
            ->setChats($chatIds)
            ->setText($this->message)
            ->startSync();
    }
}
