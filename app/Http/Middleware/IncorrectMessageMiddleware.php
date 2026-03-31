<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\BotCommands\Exceptions\IncorrectMessageException;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class IncorrectMessageMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        try {
            $next($bot);
        } catch (IncorrectMessageException $e) {
            if ($e->shouldClearConversation()) {
                $user = $bot->user();
                if ($user) {
                    $bot->endConversation();
                }
            }

            $bot->sendMessage([
                'text' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Command failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);

            $bot->sendMessage([
                'text' => 'При выполнении команды произошла ошибка на стороне сервера. Попробуйте позже.',
            ]);
        }
    }
}
