<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\ParserService;
use App\Models\Homework;
use App\Repositories\ScheduleRepository;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\DateSelectionMenu;
use App\Telegram\Messages\HomeworkList;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Nutgram;

class ShowHomeworkConversation extends BaseConversation
{
    use DateSelectionMenu;
    use HomeworkList;

    public function __construct(private ParserService $parser, private ScheduleRepository $scheduleRepository) {}

    public ?int $userId = null;

    public ?string $dateRange = null;

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class === null) {
            $this->replyAndEnd($bot, __('error.homework.not_in_class'));

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->makeOptionSelectionMenu([
            ['text' => __('button_labels.keyboard.tomorrow'), 'data' => 'showhomework.date.tomorrow'],
            ['text' => __('button_labels.keyboard.this_week'), 'data' => 'showhomework.date.this_week'],
            ['text' => __('button_labels.keyboard.next_week'), 'data' => 'showhomework.date.next_week'],
            ['text' => __('button_labels.keyboard.custom'), 'data' => 'showhomework.date.custom'],
        ]);
        $this->replyAndProceed($bot, __('prompt.homework.select_period'), 'dateSelection', $keyboard);
    }

    public function dateSelection(Nutgram $bot): void
    {
        $selectedRange = $this->getCallbackAnswerOrError($bot, 'showhomework.date', 'dateSelection');

        if ($selectedRange === false) {
            return;
        }

        if ($selectedRange === 'custom') {
            $this->replyAndProceed($bot, __('prompt.homework.enter_date_format'), 'promptDate');

            return;
        }

        $this->dateRange = $selectedRange;

        $this->showHomework($bot);
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

        $parsed = $this->parser->parseDate($input);
        if ($parsed == null) {
            $this->replyAndProceed($bot, __('error.homework.date_invalid'), 'promptDate');

            return;
        }
        $this->dateRange = $parsed->format('Y-m-d');

        $this->showHomework($bot);
    }

    private function showHomework(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $startDate = $this->dateRange;
        $endDate = $this->dateRange;

        if ($this->dateRange === 'tomorrow') {
            $startDate = now()->addDay()->toDateString();
            $endDate = $startDate;
        } elseif ($this->dateRange === 'this_week') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->addDay()->toDateString();
        } elseif ($this->dateRange === 'next_week') {
            $startDate = now()->addWeek()->startOfWeek()->toDateString();
            $endDate = now()->addWeek()->endOfWeek()->addDay()->toDateString();
        }

        $homeworks = Homework::where('class_id', $user->class_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $schedule = $this->scheduleRepository->getSchedule($user->class_id);

        [$startDate, $endDate] = [Carbon::parse($startDate), Carbon::parse($endDate)];

        $message = ($startDate->isSameDay($endDate))
            ? $this->makeHomeworkListOnDay($startDate, $schedule, collect($homeworks))
            : $this->makeHomeworkListOnWeek($startDate, $schedule, collect($homeworks));
        $this->replyAndEnd($bot, $message);
    }
}
