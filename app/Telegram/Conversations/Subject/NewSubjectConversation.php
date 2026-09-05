<?php

namespace App\Telegram\Conversations\Subject;

use App\Actions\Subject\CreateSubjectAction;
use App\Exceptions\InvalidInputException;
use App\Telegram\Conversations\BaseConversation;
use SergiX44\Nutgram\Nutgram;

class NewSubjectConversation extends BaseConversation
{
    public function __construct(private CreateSubjectAction $createSubject) {}

    public function start(Nutgram $bot)
    {
        $this->replyAndProceed($bot, __('prompt.subject.enter_name'), 'promptName');
    }

    public function promptName(Nutgram $bot)
    {
        $name = $bot->message()->text;
        $user = $this->getUser($bot);

        try {
            $subject = ($this->createSubject)($name, $user->class_id);
        } catch (InvalidInputException $e) {
            $this->errorAndProceed($e->getMessage().' '.__('error.try_again'), 'promptName');

            return;
        }

        $this->replyAndEnd($bot, __('info.subject.created', ['name' => $subject->name]));
    }
}
