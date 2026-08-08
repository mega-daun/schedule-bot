<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Schedule;

use App\Actions\Schedule\CreateScheduleAction;
use App\DataObjects\Schedule\Schedule;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\MessageTextGenerator;
use App\Helpers\ParserService;
use App\Models\Subject;
use App\Models\User;
use App\Telegram\Menus\ConfirmationMenu;
use App\Telegram\Menus\SubjectSelectionMenu;
use App\Telegram\Menus\WeekdaySelectionMenu;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class NewScheduleConversation extends Conversation
{
    use ConfirmationMenu, SubjectSelectionMenu, WeekdaySelectionMenu;

    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser, private CreateScheduleAction $createScheduleAction, private MessageTextGenerator $messageGenerator) {}

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
        if (! $this->validateCallbackData($bot, 'newschedule.weekday')) {
            $this->sendErrorMessage($bot, 'handleWorkDaysSelection');

            return;
        }
        [$action, $weekdayNum] = explode('.', $this->parser->parseCallbackData($bot->callbackQuery()->data));
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
        if (! $this->validateCallbackData($bot, 'newschedule.select')) {
            $this->sendErrorMessage($bot, 'handleLessonsSelection');

            return;
        }

        $data = $this->parser->parseCallbackData($bot->callbackQuery()->data);
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
            'messages/Schedule/weekday_schedule_preview',
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
        if (! $this->validateCallbackData($bot, 'newschedule.confirm')) {
            $this->sendErrorMessage($bot, 'workDayScheduleConfirmation');

            return;
        }
        $answer = $this->parser->parseCallbackData($bot->callbackQuery()->data);
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
                $this->sendErrorMessage($bot, 'workDayScheduleConfirmation');
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
            $bot->sendMessage(__('error.server.error'));
            $this->end();

            return;
        }
        $bot->sendMessage(__('info.schedule.created'));
        $this->end();

    }

    private function validateCallbackData(Nutgram $bot, string $prefix): bool
    {
        if (! $bot->isCallbackQuery()) {
            return false;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, $prefix)) {
            return false;
        }

        return true;
    }

    private function sendErrorMessage(Nutgram $bot, string $gotoStep, ?string $error = null): void
    {
        if ($error == null) {
            $error = __('prompt.general.click_button');
        }
        $bot->sendMessage($error);
        $this->next($gotoStep);
    }

    private function createSchedule(): bool
    {
        return ($this->createScheduleAction)($this->class_id, $this->schedule);
    }
}
