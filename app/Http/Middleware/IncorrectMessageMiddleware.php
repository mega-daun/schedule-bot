<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\IncorrectMessageException;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class IncorrectMessageMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        try {
            $next($bot);
        } catch (IncorrectMessageException $e) {
            $bot->sendMessage($e->getMessage());
            if ($e->shouldClearConversation()) {
                $bot->endConversation();
            }

        } catch (\Exception $e) {
            Log::error('Command failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);

            $bot->sendMessage(
                __('error.server.error'),
            );
        }
    }
}
