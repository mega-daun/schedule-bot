<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTelegramMessage implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int|string $chatId,
        private readonly string $text,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => $this->text,
        ]);
    }
}
