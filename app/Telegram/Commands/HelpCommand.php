<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Nutgram;

class HelpCommand extends BaseCommand
{
    private array $messageLines = [];

    public function __invoke(Nutgram $bot): void
    {
        $user = $this->getOrAddUser($bot);

        $this->messageLines[] = __('info.help.title');

        $this->addChapter(__('info.help.base'), 'start', 'cancel', 'help');

        if ($user?->hasClass()) {
            $this->addChapter(__('info.help.class'), 'leaveclass');
            $this->addChapter(__('info.help.homework'), 'showhomework', 'newhomework', 'deletehomework');

            if ($user->isOnDutyOrHigher()) {
                $this->addChapter(__('info.help.subjects'), 'newsubject', 'deletesubject');
                $this->addChapter(__('info.help.schedule'), 'newschedule');
            }

            if ($user->isAdmin()) {
                $this->addChapter(__('info.help.administration'), 'deleteclass', 'changerole');
            }
        } else {
            $this->addChapter(__('info.help.no_class'), 'newclass', 'joinclass');
        }

        $this->reply($bot, $this->constructMessage());
    }

    private function addCommandDescriptions(string ...$commandNames): void
    {
        foreach ($commandNames as $name) {
            $this->messageLines[] = $this->commandLine('/' . $name, __('command_descriptions.cmd.' . $name));
        }
    }

    private function addChapter(string $header, string ...$commandNames): void
    {
        $this->messageLines[] = '';
        $this->messageLines[] = $header;
        $this->addCommandDescriptions(...$commandNames);
    }

    private function constructMessage(): string
    {
        return implode("\n", $this->messageLines);
    }

    private function commandLine(string $command, string $description): string
    {
        return __('info.help.format', ['command' => $command, 'description' => $description]);
    }
}
