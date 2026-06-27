<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\DateHelper;
use App\Models\Homework;
use App\Models\User;
use DateTime;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class ShowHomeworkConversation extends Conversation
{
    use DateHelper;

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

        $keyboard = $this->buildDateRangeKeyboard(
            'showhomework.date',
            ['tomorrow' => 'Завтра']
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

        $selectedRange = $this->parseDateRange($callbackData);

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

        $parsed = $this->parseTextDate($input);

        if ($parsed === null) {
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

        if ($homeworks->isEmpty()) {
            $bot->sendMessage('Нет домашних заданий за выбранный период');
            $this->end();

            return;
        }

        $message = $this->formatHomeworkMessage($homeworks, $startDate, $endDate);
        $bot->sendMessage($message, parse_mode: ParseMode::MARKDOWN);
        $this->end();
    }

    private function formatHomeworkMessage($homeworks, string $startDate, string $endDate): string
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        if ($startDate === $endDate) {
            $header = '# ДЗ на '.$start->format('d.m');
        } else {
            $header = '# ДЗ на '.$start->format('d.m').'-'.$end->format('d.m');
        }

        $days = $homeworks->groupBy('date');
        $sections = [];

        foreach ($days as $date => $items) {
            $dateObj = new DateTime($date);
            $dayName = $this->getDayName($dateObj->format('N'));
            $dateHeader = '#### '.$dateObj->format('d.m').' - '.$dayName;

            $itemsList = $items->map(fn ($item) => '- '.$item->description)->implode("\n");

            $sections[] = $dateHeader."\n".$itemsList;
        }

        return $header."\n---\n".implode("\n---\n", $sections);
    }

    private function getDayName(string $dayNumber): string
    {
        $days = [
            '1' => 'Понедельник',
            '2' => 'Вторник',
            '3' => 'Среда',
            '4' => 'Четверг',
            '5' => 'Пятница',
            '6' => 'Суббота',
            '7' => 'Воскресенье',
        ];

        return $days[$dayNumber] ?? '';
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function parseDateRange(string $data): string
    {
        return explode('.', $data)[2];
    }
}
