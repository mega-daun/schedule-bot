<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\MessageTextGenerator;
use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\User;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class ShowHomeworkConversation extends Conversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser) {}

    public ?int $userId = null;

    public ?string $dateRange = null;

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class === null) {
            $bot->sendMessage(__('error.homework.not_in_class'));
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'showhomework.date',
            collect([
                ['text' => __('button_labels.keyboard.tomorrow'), 'data' => 'tomorrow'],
                ['text' => __('button_labels.keyboard.this_week'), 'data' => 'thisweek'],
                ['text' => __('button_labels.keyboard.next_week'), 'data' => 'nextweek'],
                ['text' => __('button_labels.keyboard.custom'), 'data' => 'custom'],
            ]),
            fn ($item) => $item['text'],
            fn ($item) => $item['data']
        );
        $bot->sendMessage(text: __('prompt.homework.select_period'), reply_markup: $keyboard);

        $this->next('dateSelection');
    }

    public function dateSelection(Nutgram $bot): void
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('dateSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'showhomework.date.')) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('dateSelection');

            return;
        }

        $selectedRange = $this->parser->parseCallbackData($callbackData);

        if ($selectedRange === 'custom') {
            $bot->sendMessage(__('prompt.homework.enter_date_format'));
            $this->next('promptDate');

            return;
        }

        $this->dateRange = $selectedRange;

        $this->showHomework($bot);
    }

    public function promptDate(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $bot->sendMessage(__('prompt.general.enter_date_text'));
            $this->next('promptDate');

            return;
        }

        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $bot->sendMessage(__('error.homework.date_empty'));
            $this->next('promptDate');

            return;
        }

        $parsed = $this->parser->parseDate($input);
        if ($parsed == null) {
            $bot->sendMessage(__('error.homework.date_invalid'));
            $this->next('promptDate');

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
        } elseif ($this->dateRange === 'thisweek') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->subDay()->toDateString();
        } elseif ($this->dateRange === 'nextweek') {
            $startDate = now()->addWeek()->startOfWeek()->toDateString();
            $endDate = now()->addWeek()->subDay()->endOfWeek()->toDateString();
        }

        $homeworks = Homework::where('class_id', $user->class_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $message = (new MessageTextGenerator)->homeworkView($homeworks, Carbon::parse($startDate), Carbon::parse($endDate));
        $bot->sendMessage($message);
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
