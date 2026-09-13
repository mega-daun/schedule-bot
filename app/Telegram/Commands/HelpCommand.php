<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class HelpCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $user = User::find($bot->userId());

        $lines = [
            __('info.help.title'),
            '',
            __('info.help.base'),
            $this->commandLine('/start', __('command_descriptions.cmd.start')),
            $this->commandLine('/cancel', __('command_descriptions.cmd.cancel')),
            $this->commandLine('/help', __('command_descriptions.cmd.help')),
        ];

        if ($user?->hasClass()) {
            $lines[] = '';
            $lines[] = __('info.help.class');
            $lines[] = $this->commandLine('/showhomework', __('command_descriptions.cmd.showhomework'));
            $lines[] = $this->commandLine('/leaveclass', __('command_descriptions.cmd.leaveclass'));

            if ($user->isOnDutyOrHigher()) {
                $lines[] = '';
                $lines[] = __('info.help.homework');
                $lines[] = $this->commandLine('/newhomework', __('command_descriptions.cmd.newhomework'));
                $lines[] = $this->commandLine('/deletehomework', __('command_descriptions.cmd.deletehomework'));

                $lines[] = '';
                $lines[] = __('info.help.subjects');
                $lines[] = $this->commandLine('/newsubject', __('command_descriptions.cmd.newsubject'));
                $lines[] = $this->commandLine('/deletesubject', __('command_descriptions.cmd.deletesubject'));

                $lines[] = '';
                $lines[] = __('info.help.schedule');
                $lines[] = $this->commandLine('/newschedule', __('command_descriptions.cmd.newschedule'));
            }

            if ($user->isAdmin()) {
                $lines[] = '';
                $lines[] = __('info.help.administration');
                $lines[] = $this->commandLine('/deleteclass', __('command_descriptions.cmd.deleteclass'));
                $lines[] = $this->commandLine('/changerole', __('command_descriptions.cmd.changerole'));
            }
        } else {
            $lines[] = '';
            $lines[] = __('info.help.no_class');
            $lines[] = $this->commandLine('/newclass', __('command_descriptions.cmd.newclass'));
            $lines[] = $this->commandLine('/joinclass', __('command_descriptions.cmd.joinclass'));
        }

        $bot->sendMessage(text: implode("\n", $lines));
    }

    private function commandLine(string $command, string $description): string
    {
        return __('info.help.format', ['command' => $command, 'description' => $description]);
    }
}
