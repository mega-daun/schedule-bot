<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\DateHelper;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\MessageTextGenerator;
use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use DateTime;
use Exception;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class ShowHomeworkConversation extends Conversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser)
    {
    }

    public ?int $userId = null;

    public ?string $dateRange = null;

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class === null) {
            $bot->sendMessage('Вы не состоите в классе');
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'showhomework.date', 
            collect([
                ['text' => 'На завтра', 'data' => 'tomorrow'],
                ['text' => 'На эту неделю', 'data' => 'thisweek'],
                ['text' => 'На следующую неделю', 'data' => 'nextweek'],
                ['text' => 'Свой вариант', 'data' => 'custom'],
            ]),
            fn ($item) => $item['text'],
            fn ($item) => $item['data']
        );
        $bot->sendMessage(text: 'Выберите период', reply_markup: $keyboard);

        $this->next('dateSelection');
    }

    public function dateSelection(Nutgram $bot): void
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('dateSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'showhomework.date.')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('dateSelection');

            return;
        }

        $selectedRange = $this->parser->parseCallbackData($callbackData);

        if ($selectedRange === 'custom') {
            $bot->sendMessage('Введите дату в формате ДД, ДД.ММ или ДД.ММ.ГГГГ');
            $this->next('promptDate');

            return;
        }

        $this->dateRange = $selectedRange;

        $this->showHomework($bot);
    }

    public function promptDate(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $bot->sendMessage('Введите дату текстом или введите /cancel для отмены.');
            $this->next('promptDate');

            return;
        }

        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $bot->sendMessage('Дата не может быть пустой. Введите дату в формате ДД, ДД.ММ или ДД.ММ.ГГГГ');
            $this->next('promptDate');

            return;
        }

        $parsed = $this->parser->parseDate($input);
        if ($parsed == null) {
            $bot->sendMessage('Неверный формат даты. Введите дату в формате ДД, ДД.ММ или ДД.ММ.ГГГГ');
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

        
        $message = (new MessageTextGenerator)->homeworkView($homeworks, new DateTime($startDate), new DateTime($endDate));
        $bot->sendMessage($message);
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
