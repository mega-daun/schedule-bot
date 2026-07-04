<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\DateHelper;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
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

    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser)
    {
    }

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if ($user->class === null) {
            $bot->sendMessage('Вы не состоите в классе');
            $this->end();

            return;
        }

        $this->userId = $user->id;

        $dayNum = (int)now()->format('N');
        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'newhomework.date',
            collect([
                ['text' => 'На следующий понедельник', 'data' => (clone now())->modify('+'.(7 - $dayNum + 1).' days')->format('Y-m-d')],
                ['text' => 'На следующий вторник', 'data' => (clone now())->modify('+'.(7 - $dayNum + 2).' days')->format('Y-m-d')],
                ['text' =>'На следующую среду', 'data' => (clone now())->modify('+'.(7 - $dayNum + 3).' days')->format('Y-m-d')],
                ['text' => 'На следующий четверг', 'data' => (clone now())->modify('+'.(7 - $dayNum + 4).' days')->format('Y-m-d')],
                ['text' => 'На следующую пятницу', 'data' => (clone now())->modify('+'.(7 - $dayNum + 5).' days')->format('Y-m-d')],
                ['text' => 'На следующую субботу', 'data' => (clone now())->modify('+'.(7 - $dayNum + 6).' days')->format('Y-m-d')],
                ['text' => 'Свой вариант', 'data' => 'custom'],
            ]),
            fn ($item) => $item['text'],
            fn ($item) => $item['data']
        );
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

        $selectedDate = $this->parser->parseCallbackData($callbackData);

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

        $parsed = $this->parser->parseDate(trim($input));
        if ($parsed == null) {
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
}
