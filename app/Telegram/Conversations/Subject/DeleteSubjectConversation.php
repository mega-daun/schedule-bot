<?php

namespace App\Telegram\Conversations\Subject;

use App\Actions\Subject\DeleteSubjectAction;
use App\Exceptions\InvalidInputException;
use App\Repositories\SubjectRepository;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\SubjectSelectionMenu;
use SergiX44\Nutgram\Nutgram;

class DeleteSubjectConversation extends BaseConversation
{
    use SubjectSelectionMenu;

    public function __construct(
        private DeleteSubjectAction $deleteSubject,
        private SubjectRepository $subjectRepository,
    ) {}

    public ?int $class_id = null;

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $subjects = $this->subjectRepository->getSubjects($user->class_id);
        $this->class_id = (int) $user->class_id;

        $keyboard = $this->makeSubjectSelectionMenu($subjects->toArray(), 'deletesubject.select');
        $this->replyAndProceed($bot, __('prompt.subject.select_for_delete'), 'handleSelection', $keyboard);
    }

    public function handleSelection(Nutgram $bot)
    {
        $subjectId = $this->getCallbackAnswerOrError($bot, 'deletesubject.select', 'handleSelection');

        try {
            ($this->deleteSubject)((int) $subjectId, $this->class_id);
        } catch (InvalidInputException $e) {
            $this->errorAndProceed($e->getMessage().__('error.try_again'), 'handleSelection');

            return;
        }

        $this->replyAndEnd($bot, __('info.subject.deleted'));
    }
}
