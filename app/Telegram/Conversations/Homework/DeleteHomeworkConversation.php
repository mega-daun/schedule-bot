<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Enums\UserRole;
use App\Helpers\DateHelper;
use App\Models\Homework;
use App\Models\User;
use DateTime;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class DeleteHomeworkConversation extends Conversation
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

        if (! in_array($user->role, [UserRole::Teacher, UserRole::Admin, UserRole::OnDuty])) {
            $bot->sendMessage('У вас нет прав для удаления домашних заданий');
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->buildDateRangeSelectionKeyboard();
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

        if (! str_starts_with($callbackData, 'deletehomework.date.')) {
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

        $this->showHomeworkList($bot);
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

        $this->showHomeworkList($bot);
    }

    public function homeworkSelection(Nutgram $bot): void
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('homeworkSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'deletehomework.select.')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('homeworkSelection');

            return;
        }

        $homeworkId = $this->parseHomeworkId($callbackData);

        $homework = Homework::find($homeworkId);

        if ($homework === null) {
            $bot->sendMessage('Домашнее задание не найдено, возможно уже удалено');
            $this->end();

            return;
        }

        $user = $this->getUser($bot);

        if ($homework->class_id !== $user->class_id) {
            $bot->sendMessage('Домашнее задание не найдено');
            $this->end();

            return;
        }

        $homework->delete();

        $bot->sendMessage('Домашнее задание успешно удалено');
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function showHomeworkList(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $startDate = $this->dateRange;
        $endDate = $this->dateRange;

        if ($this->dateRange === 'thisweek') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->toDateString();
        } elseif ($this->dateRange === 'nextweek') {
            $startDate = now()->addWeek()->startOfWeek()->toDateString();
            $endDate = now()->addWeek()->endOfWeek()->toDateString();
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

        $keyboard = $this->buildHomeworkSelectionKeyboard($homeworks);
        $bot->sendMessage(text: 'Выберите домашнее задание для удаления', reply_markup: $keyboard);

        $this->next('homeworkSelection');
    }

    private function buildDateRangeSelectionKeyboard(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow([
                InlineKeyboardButton::make(
                    text: 'Эта неделя',
                    callback_data: 'deletehomework.date.thisweek'
                ),
            ])
            ->addRow([
                InlineKeyboardButton::make(
                    text: 'Следующая неделя',
                    callback_data: 'deletehomework.date.nextweek'
                ),
            ])
            ->addRow([
                InlineKeyboardButton::make(
                    text: 'Свой вариант',
                    callback_data: 'deletehomework.date.custom'
                ),
            ]);
    }

    private function buildHomeworkSelectionKeyboard($homeworks): InlineKeyboardMarkup
    {
        $buttons = [];

        foreach ($homeworks as $hw) {
            $date = (new DateTime($hw->date))->format('d.m');
            $description = $this->truncateDescription($hw->description);
            $text = "{$date} - {$description}";

            $buttons[] = InlineKeyboardButton::make(
                text: $text,
                callback_data: "deletehomework.select.{$hw->id}"
            );
        }

        $markup = InlineKeyboardMarkup::make();

        foreach (array_chunk($buttons, 2) as $row) {
            $markup->addRow($row);
        }

        return $markup;
    }

    private function parseDateRange(string $data): string
    {
        return explode('.', $data)[2];
    }

    private function parseHomeworkId(string $data): int
    {
        return (int) explode('.', $data)[2];
    }
}
