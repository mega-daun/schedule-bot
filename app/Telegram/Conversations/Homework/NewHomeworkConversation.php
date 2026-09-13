<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\Subject;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\DateSelectionMenu;
use App\Telegram\Menus\SubjectSelectionMenu;
use SergiX44\Nutgram\Nutgram;

class NewHomeworkConversation extends BaseConversation
{
    use DateSelectionMenu, SubjectSelectionMenu;

    private const MIN_DESCRIPTION_LENGTH = 12;

    public ?int $userId = null;

    public ?string $date = null;

    public ?string $description = null;

    public ?int $subjectId = null;

    public function __construct(private ParserService $parser) {}

    public function start(Nutgram $bot): void
    {
        $this->userId = $this->getUser($bot)->id;

        $keyboard = $this->makeFutureWeekdaySelectionMenu([1, 2, 3, 4, 5, 6], 'newhomework.date');
        $this->replyAndProceed($bot, __('prompt.homework.select_date'), 'dateSelection', $keyboard);
    }

    public function dateSelection(Nutgram $bot): void
    {
        $selectedDate = $this->getCallbackAnswerOrError($bot, 'newhomework.date', 'dateSelection');
        if ($selectedDate === false) {
            return;
        }

        if ($selectedDate === 'custom') {
            $this->replyAndProceed($bot, __('prompt.homework.enter_date_format'), 'promptDate');

            return;
        }

        $this->date = $selectedDate;

        $subjects = $this->getUser($bot)->class->subjects->map(fn (Subject $s) => ['name' => $s->name, 'id' => $s->id])->values()->toArray();
        $keyboard = $this->makeSubjectSelectionMenu($subjects, 'newhomework.subject');
        $this->replyAndProceed($bot, __('prompt.homework.select_subject'), 'subjectSelection', $keyboard);
    }

    public function promptDate(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $this->replyAndProceed($bot, __('prompt.general.enter_date_text'), 'promptDate');

            return;
        }

        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $this->replyAndProceed($bot, __('error.homework.date_empty'), 'promptDate');

            return;
        }

        $parsed = $this->parser->parseDate(trim($input));
        if ($parsed == null) {
            $this->replyAndProceed($bot, __('error.homework.date_invalid'), 'promptDate');

            return;
        }

        $this->date = $parsed->format('Y-m-d');

        $subjects = $this->getUser($bot)->class->subjects->map(fn (Subject $s) => ['name' => $s->name, 'id' => $s->id])->values()->toArray();
        $keyboard = $this->makeSubjectSelectionMenu($subjects, 'newhomework.subject');
        $this->replyAndProceed($bot, __('prompt.homework.select_subject'), 'subjectSelection', $keyboard);
    }

    public function subjectSelection(Nutgram $bot): void
    {
        $subjectId = $this->getCallbackAnswerOrError($bot, 'newhomework.subject', 'subjectSelection');
        if ($subjectId === false) {
            return;
        }

        if (! in_array($subjectId, $this->getUser($bot)->class->subjects->pluck('id')->map(fn ($id) => (string) $id)->toArray())) {
            $this->errorAndProceed(__('prompt.general.click_button'), 'subjectSelection');

            return;
        }

        $this->subjectId = (int) $subjectId;
        $this->replyAndProceed($bot, __('prompt.homework.enter_description'), 'promptDescription');
    }

    public function promptDescription(Nutgram $bot): void
    {
        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $this->replyAndProceed($bot, __('error.homework.description_empty'), 'promptDescription');

            return;
        }

        $text = trim($input);

        if (mb_strlen($text) < self::MIN_DESCRIPTION_LENGTH) {
            $this->replyAndProceed($bot, __('error.homework.description_too_short', ['min' => self::MIN_DESCRIPTION_LENGTH]), 'promptDescription');

            return;
        }

        $this->description = $text;

        $user = $this->getUser($bot);

        Homework::create([
            'class_id' => $user->class_id,
            'date' => $this->date,
            'description' => $this->description,
            'subject_id' => $this->subjectId,
        ]);

        $this->replyAndEnd($bot, __('info.homework.created'));
    }
}
