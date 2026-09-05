<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Actions\Class\JoinClassAction;
use App\Telegram\Conversations\BaseConversation;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class JoinClassConversation extends BaseConversation
{
    public function __construct(private JoinClassAction $join_class) {}

    public function start(Nutgram $bot)
    {
        $this->replyAndProceed($bot, __('prompt.class.enter_token'), 'handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $token = trim($bot->message()->text);

        try {
            $this->join_class->byToken($token, $user);
        } catch (InvalidArgumentException $e) {
            $this->errorAndEnd($e->getMessage());
        }

        $this->replyAndEnd($bot, __('info.class.joined'));
    }
}
