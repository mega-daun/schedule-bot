<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Schedule;

use App\Actions\Schedule\CreateScheduleAction;
use App\DataObjects\Schedule\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\ConfirmationMenu;
use App\Telegram\Menus\SubjectSelectionMenu;
use App\Telegram\Menus\WeekdaySelectionMenu;
use SergiX44\Nutgram\Nutgram;

class NewScheduleConversation extends BaseConversation
{
    use ConfirmationMenu, SubjectSelectionMenu, WeekdaySelectionMenu;

    public function __construct(private CreateScheduleAction $createScheduleAction) {}

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
        $this->class_id = User::where('id', $bot->user()->id)->get(['class_id'])->first()->class_id;
        $this->subjects = Subject::where('class_id', $this->class_id)->get(['name', 'id'])->toArray();

        $this->sendWeekdaySelectionMenu($bot);
        $this->next('handleWorkDaysSelection');
    }

    private function sendWeekdaySelectionMenu(Nutgram $bot): void
    {
        $keyboard = $this->makeMultipleWeekdaySelectionMenu($this->schedule->getWorkdays(), 'newschedule.weekday');
        $bot->sendMessage(__('prompt.schedule.select_weekdays'), reply_markup: $keyboard);
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
                    $bot->sendMessage(__('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]));
                    $this->sendSubjectSelectionMenu($bot);

                    $this->next('handleLessonsSelection');

                    return;
                }
                $bot->sendMessage(__('prompt.schedule.should_have_workdays'));
                $this->sendWeekdaySelectionMenu($bot);
                $this->next('handleWorkDaysSelection');

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
                $bot->sendMessage(__('prompt.schedule.no_lessons'));
                $this->sendSubjectSelectionMenu($bot);

                $this->next('handleLessonsSelection');

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
        $bot->sendMessage(__('prompt.schedule.select_subjects', ['lesson_number' => $this->currentLesson]), reply_markup: $keyboard);
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
        $bot->sendMessage($preview);
        $bot->sendMessage(__('prompt.schedule.confirm_schedule'), reply_markup: $this->makeConfirmationMenu('newschedule.confirm'));
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
                $bot->sendMessage(__('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]));
                $this->sendSubjectSelectionMenu($bot);
                $this->next('handleLessonsSelection');

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
            $bot->sendMessage(__('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.'.$this->currentWeekday))]));
            $this->sendSubjectSelectionMenu($bot);
            $this->next('handleLessonsSelection');

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
