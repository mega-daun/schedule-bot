<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Actions\Class\CreateClassAction;
use App\Actions\Class\JoinClassAction;
use App\Enums\UserRole;
use App\Telegram\Conversations\BaseConversation;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class NewClassConversation extends BaseConversation
{
    public function __construct(private CreateClassAction $create_class, private JoinClassAction $join_class) {}

    public function start(Nutgram $bot)
    {
        $this->replyAndProceed($bot, __('prompt.class.enter_name'), 'handleNamePrompt');
    }

    public function handleNamePrompt(Nutgram $bot)
    {
        $name = $bot->message()->text;
        $user = $this->getUser($bot);

        try {
            $class = ($this->create_class)($name);
            ($this->join_class)($class->id, $user, UserRole::Admin);
        } catch (InvalidArgumentException $e) {
            $this->errorAndEnd($e->getMessage());
        }

        $this->replyAndEnd(
            bot: $bot,
            message: __('info.class.created', ['code' => $class->code, 'token' => $class->join_token])
        );
    }
}
