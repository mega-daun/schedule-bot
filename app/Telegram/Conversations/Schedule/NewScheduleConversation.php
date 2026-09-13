<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Schedule;

use App\Actions\Schedule\CreateScheduleAction;
use App\DataObjects\Schedule\Schedule;
use App\Repositories\SubjectRepository;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\ConfirmationMenu;
use App\Telegram\Menus\SubjectSelectionMenu;
use App\Telegram\Menus\WeekdaySelectionMenu;
use SergiX44\Nutgram\Nutgram;

class NewScheduleConversation extends BaseConversation
{
    use ConfirmationMenu, SubjectSelectionMenu, WeekdaySelectionMenu;

    public function __construct(private CreateScheduleAction $createScheduleAction, private SubjectRepository $subjectRepository) {}

    protected function beforeStep(Nutgram $bot): void
    {
        if (is_string($this->schedule)) {
            $this->schedule = Schedule::fromJson($this->schedule);
        } elseif ($this->schedule === null) {
            $this->schedule = new Schedule([]);
        }
    }

    public int $currentWeekday = 0;

    public int $currentLesson = 0;

    public array $subjects = [];

    private Schedule|string|null $schedule = null;

    public ?int $class_id = null;

    public function getSerializableAttributes(): array
    {
        return [
            ...parent::getSerializableAttributes(),
            'schedule' => $this->schedule->toJson(),
        ];
    }

    public function start(Nutgram $bot)
    {
        $this->class_id = $this->getUser($bot, with: ['class' => ['subjects']])->class_id;
        $this->subjects = $this->subjectRepository->getSubjects($this->class_id, ['name', 'id'])->toArray();

        $this->sendWeekdaySelectionMenu($bot);
        $this->next('handleWorkDaysSelection');
    }

    private function sendWeekdaySelectionMenu(Nutgram $bot): void
    {
        $keyboard = $this->makeMultipleWeekdaySelectionMenu($this->schedule->getWorkdays(), 'newschedule.weekday');
        $this->reply($bot, __('prompt.schedule.select_weekdays'), $keyboard);
    }

    public function handleWorkDaysSelection(Nutgram $bot)
    {
        $answer = $this->getCallbackAnswerOrError($bot, 'newschedule.weekday', 'handleWorkDaysSelection');
        if ($answer === false) {
            return;
        }
        [$action, $weekdayNum] = explode('.', $answer);
        switch ($action) {
            case 'done':
                $hasNextWorkDay = $this->switchToTheNextWorkDay();
                if ($hasNextWorkDay) {
                    $this->replyAndProceed($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]), 'handleLessonsSelection');
                    $this->sendSubjectSelectionMenu($bot);

                    return;
                }
                $this->replyAndProceed($bot, __('prompt.schedule.should_have_workdays'), 'handleWorkDaysSelection');
                $this->sendWeekdaySelectionMenu($bot);

                return;
            case 'add':
                $this->schedule->addWorkDay((int) $weekdayNum);
                break;
            case 'remove':
                $this->schedule->removeWorkDay((int) $weekdayNum);
                break;
        }
        $this->sendWeekdaySelectionMenu($bot);

        $this->next('handleWorkDaysSelection');
    }

    public function switchToTheNextWorkDay(): bool
    {
        for ($i = $this->currentWeekday + 1; $i <= 7; $i++) {
            if ($this->schedule->hasWorkday($i)) {
                $this->currentWeekday = $i;
                $this->currentLesson = 1;

                return true;
            }
        }

        return false;
    }

    public function handleLessonsSelection(Nutgram $bot)
    {
        $data = $this->getCallbackAnswerOrError($bot, 'newschedule.select', 'handleLessonsSelection');
        if ($data === false) {
            return;
        }
        if ($data == 'done') {
            if ($this->schedule->getLessons($this->currentWeekday)->isEmpty()) {
                $this->replyAndProceed($bot, __('prompt.schedule.no_lessons'), 'handleLessonsSelection');
                $this->sendSubjectSelectionMenu($bot);

                return;
            }

            $this->sendConfirmationPrompt($bot);
            $this->next('workDayScheduleConfirmation');

            return;
        }
        $subjectId = $data;
        $subjectName = array_find($this->subjects, fn (array $subject) => $subject['id'] == (int) $subjectId)['name'];

        $this->schedule->addLesson($this->currentWeekday, (int) $subjectId, $subjectName);
        $this->currentLesson += 1;

        $this->sendSubjectSelectionMenu($bot);

        $this->next('handleLessonsSelection');
    }

    private function sendSubjectSelectionMenu(Nutgram $bot): void
    {
        $keyboard = $this->currentLesson != 1
            ? $this->makeSubjectSelectionMenuWithDoneButton($this->subjects, 'newschedule.select')
            : $this->makeSubjectSelectionMenu($this->subjects, 'newschedule.select');

        $this->reply($bot, __('prompt.schedule.select_subjects', ['lesson_number' => $this->currentLesson]), $keyboard);
    }

    private function sendConfirmationPrompt(Nutgram $bot): void
    {
        $preview = view(
            'messages/schedule/weekday_schedule',
            [
                'weekday' => strtolower(__('general.weekday.'.$this->currentWeekday)),
                'lessons' => $this->schedule->getLessons($this->currentWeekday),
            ]
        )->render();
        $this->reply($bot, $preview);
        $this->reply($bot, __('prompt.schedule.confirm_schedule'), $this->makeConfirmationMenu('newschedule.confirm'));
    }

    public function workDayScheduleConfirmation(Nutgram $bot): void
    {
        $answer = $this->getCallbackAnswerOrError($bot, 'newschedule.confirm', 'workDayScheduleConfirmation');
        if ($answer === false) {
            return;
        }
        switch ($answer) {
            case 'yes':
                $this->iterateToTheNextWorkDayOrToScheduleCreation($bot);
                break;
            case 'no':
                $this->resetCurrentWorkDay();
                $this->replyAndProceed($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]), 'handleLessonsSelection');
                $this->sendSubjectSelectionMenu($bot);

                break;
            default:
                $this->errorAndProceed(__('prompt.general.click_button'), 'workDayScheduleConfirmation');
                break;
        }
    }

    private function resetCurrentWorkDay(): void
    {
        $this->currentLesson = 1;
        $this->schedule->removeWorkDay($this->currentWeekday);
        $this->schedule->addWorkDay($this->currentWeekday);
    }

    private function iterateToTheNextWorkDayOrToScheduleCreation(Nutgram $bot): void
    {
        $hasNextWeekday = $this->switchToTheNextWorkDay();
        if ($hasNextWeekday) {
            $this->replyAndProceed($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]), 'handleLessonsSelection');
            $this->sendSubjectSelectionMenu($bot);

            return;
        }
        $creationSuccess = $this->createSchedule();
        if (! $creationSuccess) {
            $this->replyAndEnd($bot, __('error.server.error'));

            return;
        }
        $this->replyAndEnd($bot, __('info.schedule.created'));
    }

    private function createSchedule(): bool
    {
        return ($this->createScheduleAction)($this->class_id, $this->schedule);
    }
}
