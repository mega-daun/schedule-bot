<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Actions\Homework\CreateHomeworkAction;
use App\Exceptions\IncorrectMessageException;
use App\Helpers\ParserService;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubjectRepository;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\DateSelectionMenu;
use App\Telegram\Menus\SubjectSelectionMenu;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class NewHomeworkConversation extends BaseConversation
{
    use DateSelectionMenu, SubjectSelectionMenu;

    public ?int $userId = null;

    public ?int $classId = null;

    public ?string $date = null;

    public ?string $description = null;

    public ?int $subjectId = null;

    public function __construct(private ScheduleRepository $scheduleRepository, private SubjectRepository $subjectRepository, private ParserService $parser, private CreateHomeworkAction $createHomeworkAction) {}

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $this->userId = $user->id;
        $this->classId = $user->class_id;

        $keyboard = $this->makeFutureWeekdaySelectionMenu(
            $this->scheduleRepository->getWorkDays($this->classId),
            'newhomework.date'
        );

        $this->replyAndProceed($bot, __('prompt.homework.select_date'), 'dateSelection', $keyboard);
    }

    public function dateSelection(Nutgram $bot): void
    {
        $selectedDate = $this->getCallbackAnswerOrError(
            $bot,
            'newhomework.date',
            'dateSelection'
        );

        if ($selectedDate === 'custom') {
            $this->replyAndProceed($bot, __('prompt.homework.enter_date_format'), 'promptDate');

            return;
        }

        $this->date = $selectedDate;

        $keyboard = $this->makeSubjectSelectionMenu(
            $this->subjectRepository->getSubjects($this->classId, ['name', 'id'])->toArray(),
            'newhomework.subject',
        );

        $this->replyAndProceed($bot, __('prompt.homework.select_subject'), 'subjectSelection', $keyboard);
    }

    public function promptDate(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $this->replyAndProceed($bot, __('prompt.general.enter_date_text'), 'promptDate');

            return;
        }

        $input = $bot->message()->text;

        $parsed = $this->parser->parseDate(trim($input));
        if ($parsed == null) {
            $this->replyAndProceed($bot, __('error.homework.date_invalid'), 'promptDate');

            return;
        }

        $this->date = $parsed->format('Y-m-d');

        $keyboard = $this->makeSubjectSelectionMenu(
            $this->subjectRepository->getSubjects($this->classId, ['name', 'id'])->toArray(),
            'newhomework.subject',
        );

        $this->replyAndProceed($bot, __('prompt.homework.select_subject'), 'subjectSelection', $keyboard);
    }

    public function subjectSelection(Nutgram $bot): void
    {
        $this->subjectId = (int) $this->getCallbackAnswerOrError($bot, 'newhomework.subject', 'subjectSelection');
        $this->replyAndProceed($bot, __('prompt.homework.enter_description'), 'promptDescription');
    }

    public function promptDescription(Nutgram $bot): void
    {
        $text = $bot->message()->text;

        try {
            ($this->createHomeworkAction)($this->classId, $this->subjectId, $this->parser->parseDate($this->date), $text);
        } catch (InvalidArgumentException $e) {
            throw new IncorrectMessageException($e->getMessage());
        }
        $this->replyAndEnd($bot, __('info.homework.created'));
    }
}
