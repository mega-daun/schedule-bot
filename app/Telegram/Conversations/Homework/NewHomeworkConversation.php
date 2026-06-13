<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Models\Homework;
use App\Models\User;
use DateTime;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

use function Symfony\Component\Clock\now;

class NewHomeworkConversation extends Conversation
{
    private const MIN_DESCRIPTION_LENGTH = 12;

    public ?int $userId = null;

    public ?string $date = null;

    public ?string $description = null;

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class === null) {
            $bot->sendMessage('Вы не состоите в классе');
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->buildDateKeyboard();
        $bot->sendMessage(text: 'Выберите дату', reply_markup: $keyboard);

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

        if (! str_starts_with($callbackData, 'newhomework.date.')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('dateSelection');

            return;
        }

        $selectedDate = $this->parseDate($callbackData);

        if ($selectedDate === 'custom') {
            $bot->sendMessage('Введите дату в формате ДД, ДД.ММ или ДД.ММ.ГГГГ');
            $this->next('promptDate');

            return;
        }

        $this->date = $selectedDate;

        $bot->sendMessage('Введите описание домашнего задания');
        $this->next('promptDescription');
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

        $this->date = $parsed->format('Y-m-d');

        $bot->sendMessage('Введите описание домашнего задания');
        $this->next('promptDescription');
    }

    public function promptDescription(Nutgram $bot): void
    {
        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $bot->sendMessage('Описание не может быть пустым. Введите описание домашнего задания');
            $this->next('promptDescription');

            return;
        }

        $text = trim($input);

        if (mb_strlen($text) < self::MIN_DESCRIPTION_LENGTH) {
            $bot->sendMessage('Описание слишком короткое. Минимум '.self::MIN_DESCRIPTION_LENGTH.' символов. Введите описание домашнего задания');
            $this->next('promptDescription');

            return;
        }

        $this->description = $text;

        $user = $this->getUser($bot);

        $exists = Homework::where('date', $this->date)
            ->where('class_id', $user->class_id)
            ->exists();

        if ($exists) {
            $bot->sendMessage('Домашнее задание на эту дату уже существует');
            $this->end();

            return;
        }

        Homework::create([
            'class_id' => $user->class_id,
            'date' => $this->date,
            'description' => $this->description,
        ]);

        $bot->sendMessage('Домашнее задание успешно создано');
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function buildDateKeyboard(): InlineKeyboardMarkup
    {
        $dayNum = (int) now()->format('N');

        $dates = [
            'На следующий понедельник' => (clone now())->modify('+'.(7 - $dayNum + 1).' days')->format('Y-m-d'),
            'На следующий вторник' => (clone now())->modify('+'.(7 - $dayNum + 2).' days')->format('Y-m-d'),
            'На следующую среду' => (clone now())->modify('+'.(7 - $dayNum + 3).' days')->format('Y-m-d'),
            'На следующий четверг' => (clone now())->modify('+'.(7 - $dayNum + 4).' days')->format('Y-m-d'),
            'На следующую пятницу' => (clone now())->modify('+'.(7 - $dayNum + 5).' days')->format('Y-m-d'),
            'На следующую субботу' => (clone now())->modify('+'.(7 - $dayNum + 6).' days')->format('Y-m-d'),
            'Свой вариант' => 'custom',
        ];

        $markup = InlineKeyboardMarkup::make();

        foreach ($dates as $name => $date) {
            $markup->addRow([
                InlineKeyboardButton::make(
                    text: $name,
                    callback_data: "newhomework.date.{$date}"
                ),
            ]);
        }

        return $markup;
    }

    protected function parseDate(string $data): string
    {
        return explode('.', $data)[2];
    }

    private function parseTextDate(string $input): ?DateTime
    {
        $input = trim($input);

        // Try YYYY-MM-DD
        $parsed = DateTime::createFromFormat('Y-m-d', $input);
        if ($parsed && $parsed->format('Y-m-d') === $input) {
            return $parsed;
        }

        // Try DD.MM.YYYY
        $parsed = DateTime::createFromFormat('d.m.Y', $input);
        if ($parsed && $parsed->format('d.m.Y') === $input) {
            return $parsed;
        }

        // Try DD.MM
        $parsed = DateTime::createFromFormat('d.m', $input);
        if ($parsed && $parsed->format('d.m') === $input) {
            return $parsed;
        }

        // Try DD
        $parsed = DateTime::createFromFormat('d', $input);
        if ($parsed && $parsed->format('d') === $input) {
            return $parsed;
        }

        return null;
    }
}
