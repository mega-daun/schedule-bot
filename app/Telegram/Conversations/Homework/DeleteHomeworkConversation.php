<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Enums\UserRole;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class DeleteHomeworkConversation extends Conversation
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

        if (! in_array($user->role, [UserRole::Teacher, UserRole::Admin, UserRole::OnDuty])) {
            $bot->sendMessage('У вас нет прав для удаления домашних заданий');
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'deletehomework.date',
            collect([
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

        if (! str_starts_with($callbackData, 'deletehomework.date.')) {
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

        $parsed = $this->parser->parseDate($input);
        if ($parsed == null) {
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

        $homeworkId = (int) $this->parser->parseCallbackData($callbackData);

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

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard('deletehomework.select', $homeworks, fn (Homework $hw) => $hw->date->format('d.m') . " - " . $hw->description, fn (Homework $hw) => $hw->id);
        $bot->sendMessage(text: 'Выберите домашнее задание для удаления', reply_markup: $keyboard);

        $this->next('homeworkSelection');
    }

}
