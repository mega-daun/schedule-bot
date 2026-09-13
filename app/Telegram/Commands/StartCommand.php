<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Actions\Class\JoinClassAction;
use App\Exceptions\IncorrectMessageException;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class StartCommand extends BaseCommand
{
    public function __construct(private JoinClassAction $joinClass) {}

    public function __invoke(Nutgram $bot, ?string $token = null): void
    {
        $user = $this->getOrAddUser($bot);

        if ($token === null) {
            $this->reply($bot, __('prompt.general.welcome', ['name' => $user->first_name]));
            return;
        }

        try {
            ($this->joinClass)->byToken($token, $user);
        } catch (InvalidArgumentException $e) {
            throw new IncorrectMessageException($e->getMessage());
        }

        $this->reply($bot, __('info.class.joined'));
    }
}
