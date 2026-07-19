<?php

namespace App\Telegram\Conversations\Schedule;

use App\Actions\Schedule\CreateScheduleAction;
use App\Exceptions\IncorrectMessageException;
use App\Exceptions\InvalidInputException;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\MessageTextGenerator;
use App\Helpers\ParserService;
use App\Models\Subject;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class NewScheduleConversation extends Conversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser, private CreateScheduleAction $createScheduleAction, private MessageTextGenerator $messageGenerator) {}

    public int $currentWeekday = 1;

    public array $selectedSubjects = [[], [], [], [], [], []];

    public ?int $class_id = null;

    public function start(Nutgram $bot)
    {
        $this->class_id = User::find($bot->user()->id)->class_id;
        $this->next('lessonsSelection');
    }

    public function lessonsSelection(Nutgram $bot)
    {
        $selectedSubjectForCurWeekday = $this->selectedSubjects[$this->currentWeekday - 1];
        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'newschedule.select',
            Subject::where('class_id', $this->class_id)->get(),
            fn (Subject $s) => in_array($s->name, $selectedSubjectForCurWeekday) ? __('prompt.subject.marked', ['name' => $s->name, 'lesson_number' => array_search($s->name, $selectedSubjectForCurWeekday) + 1]) : $s->name,
            fn (Subject $s) => in_array($s->name, $selectedSubjectForCurWeekday) ? 'remove.'.$s->name : 'add.'.$s->name,
            1,
            ['done' => 'Готово']
        );
        $bot->sendMessage($this->createLessonsPrompt(), reply_markup: $keyboard);
        $this->next('handleLessonsSelect');
    }

    public function handleLessonsSelect(Nutgram $bot)
    {
        if (! $subjectName = $this->validateCallbackData($bot, 'newschedule.select', 'createLessons')) {
            return;
        }
        if ($subjectName == 'done') {
            $this->currentWeekday += 1;
        } else {
            [$action, $subjectName] = explode('.', $subjectName);
            switch ($action) {
                case 'add': $this->addLesson($subjectName);
                case 'remove': $this->removeLesson($subjectName);
                default:
                    $bot->sendMessage(__('prompt.general.click_button'));
                    $this->next('handleLessonsSelect');

                    return;

            }
        }

        if ($this->currentWeekday > 6) {
            $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
                'newschedule.confirm',
                collect(
                    [
                        ['label' => 'Да', 'data' => 'yes'],
                        ['label' => 'Нет', 'data' => 'no'],
                    ]
                ),
                fn (array $opt) => $opt['label'],
                fn (array $opt) => $opt['data'],
            );
            $bot->sendMessage($this->messageGenerator->scheduleConfirm($this->selectedSubjects), reply_markup: $keyboard);
            $this->next('scheduleConfirm');

            return;
        }
        $this->next('lessonsSelection');
    }

    private function removeLesson(string $subjectName)
    {
        $num = array_search($subjectName, $this->selectedSubjects[$this->currentWeekday - 1]);
        unset($this->selectedSubjects[$this->currentWeekday - 1][$num]);
        $this->selectedSubjects[$this->currentWeekday - 1] = array_values($this->selectedSubjects[$this->currentWeekday - 1]);
    }

    private function addLesson(string $subjectName)
    {
        $this->selectedSubjects[$this->currentWeekday - 1][] = $subjectName;
    }

    public function scheduleConfirm(Nutgram $bot)
    {
        if (! $answer = $this->validateCallbackData($bot, 'newschedule.confirm', 'scheduleConfirm')) {
            return;
        }
        if ($answer == 'no') {
            $this->clearScheduleData();
            $bot->sendMessage(__('info.schedule.recreating'));

            $this->next('lessonsSelection');

            return;
        } elseif ($answer == 'yes') {
            $this->next('createSchedule');

            return;
        }
    }

    private function clearScheduleData()
    {
        $this->currentWeekday = 1;
        $this->selectedSubjects = [[], [], [], [], [], [], []];
    }

    public function createSchedule(Nutgram $bot)
    {
        try {
            ($this->createScheduleAction)($this->class_id, collect($this->selectedSubjects));
            $bot->sendMessage(__('info.schedule.created'));
        } catch (InvalidInputException $e) {
            throw new IncorrectMessageException($e->getMessage(), true);
        }
    }

    private function createLessonsPrompt(): string
    {
        return __('prompt.schedule.select_subjects', ['weekday' => strtolower(__('weekday.'.$this->currentWeekday))]);
    }

    private function validateCallbackData(Nutgram $bot, string $prefix, string $currentStep, ?callable $additionalValidation = null): string|bool
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next($currentStep);

            return false;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, $prefix)) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next($currentStep);

            return false;
        }

        $data = $this->parser->parseCallbackData($callbackData);

        if ($additionalValidation != null && ! $additionalValidation($data)) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next($currentStep);

            return false;
        }

        return $data;
    }
}
