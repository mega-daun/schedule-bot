<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ConversationHandler;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

class TelegramPollingCommand extends Command
{
    protected $signature = 'telegram:poll-conv';

    protected $description = 'Run Telegram bot long-polling with conversation support';

    public function handle(LoggerInterface $logger, ConversationHandler $conversationHandler): int
    {
        $telegram = TelegramFacade::bot();
        $this->info('Starting Telegram bot polling...');

        while (true) {
            try {
                $updates = $telegram->commandsHandler(false);

                if ($updates === null) {
                    sleep(1);

                    continue;
                }
                foreach ($updates as $update) {

                    if ($update === null) {
                        sleep(1);

                        continue;
                    }
                    $message = $update->getMessage();

                    if ($message === null) {
                        sleep(1);

                        continue;
                    }

                    $text = $message->text;

                    if ($text === null) {
                        sleep(1);

                        continue;
                    }

                    if (str_starts_with($text, '/')) {
                        sleep(1);

                        continue;
                    }

                    $handled = $conversationHandler->handle($update);

                    if (! $handled) {
                        $logger->debug('Unhandled message', [
                            'user_id' => $message->from->id,
                            'text' => $text,
                        ]);
                    }

                    sleep(1);
                }
            } catch (\Exception $e) {
                $logger->error('Telegram polling error: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                sleep(5);
            }
        }

        return self::SUCCESS;
    }
}
