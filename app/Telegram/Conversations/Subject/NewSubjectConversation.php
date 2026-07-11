<?php

namespace App\Telegram\Conversations\Subject;

use App\Actions\Subject\CreateSubjectAction;
use App\Exceptions\IncorrectMessageException;
use App\Exceptions\InvalidInputException;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class NewSubjectConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $bot->sendMessage(text: __('prompt.subject.enter_name'));
        $this->next('promptName');
    }

    public function promptName(Nutgram $bot)
    {
        $name = $bot->message()->text;
        try {
            $action = new CreateSubjectAction($name, $this->getUser($bot)->class_id);
            $subject = $action();
        } catch (InvalidInputException $e) {
            $this->next('promptName');
            throw new IncorrectMessageException($e->getMessage().' '.__('error.try_again'));
        }
        $bot->sendMessage(text: __('info.subject.created', ['name' => $subject->name]));
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
