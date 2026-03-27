<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BroadcastHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BroadcastToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Collection $userIds,
        private readonly string $message,
    ) {}

    public function handle(BroadcastHandler $broadcast): void
    {
        $users = User::whereIn('id', $this->userIds)->get();
        $broadcast->broadcastTo($users, $this->message);
    }
}
