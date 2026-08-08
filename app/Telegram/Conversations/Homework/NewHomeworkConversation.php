<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\ParserService;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

use function Symfony\Component\Clock\now;

class NewHomeworkConversation extends Conversation
{
    private const MIN_DESCRIPTION_LENGTH = 12;

    public ?int $userId = null;

    public ?string $date = null;

    public ?string $description = null;

    public ?int $subjectId = null;

    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser) {}

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $this->userId = $user->id;

        $dayNum = (int) now()->format('N');
        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'newhomework.date',
            collect([
                ['text' => __('button_labels.keyboard.next_monday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 1).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.next_tuesday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 2).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.next_wednesday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 3).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.next_thursday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 4).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.next_friday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 5).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.next_saturday'), 'data' => (clone now())->modify('+'.(7 - $dayNum + 6).' days')->format('Y-m-d')],
                ['text' => __('button_labels.keyboard.custom'), 'data' => 'custom'],
            ]),
            fn ($item) => $item['text'],
            fn ($item) => $item['data']
        );
        $bot->sendMessage(text: __('prompt.homework.select_date'), reply_markup: $keyboard);

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

        if (! str_starts_with($callbackData, 'newhomework.date.')) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('dateSelection');

            return;
        }

        $selectedDate = $this->parser->parseCallbackData($callbackData);

        if ($selectedDate === 'custom') {
            $bot->sendMessage(__('prompt.homework.enter_date_format'));
            $this->next('promptDate');

            return;
        }

        $this->date = $selectedDate;

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'newhomework.subject',
            $this->getUser($bot)->class->subjects,
            fn (Subject $item) => $item->name,
            fn (Subject $item) => $item->id
        );
        $bot->sendMessage(__('prompt.homework.select_subject'), reply_markup: $keyboard);
        $this->next('subjectSelection');
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

        $parsed = $this->parser->parseDate(trim($input));
        if ($parsed == null) {
            $bot->sendMessage(__('error.homework.date_invalid'));
            $this->next('promptDate');

            return;
        }

        $this->date = $parsed->format('Y-m-d');

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'newhomework.subject',
            $this->getUser($bot)->class->subjects,
            fn (Subject $item) => $item->name,
            fn (Subject $item) => $item->id
        );
        $bot->sendMessage(__('prompt.homework.select_subject'), reply_markup: $keyboard);
        $this->next('subjectSelection');
    }

    public function subjectSelection(Nutgram $bot): void
    {
        if (! $subjectId = $this->validateCallbackData(
            $bot,
            'newhomework.subject',
            'subjectSelection',
            fn (string $data) => in_array($data, $this->getUser($bot)->class->subjects->pluck('id')->toArray())
        )
        ) {
            return;
        }
        $this->subjectId = (int) $subjectId;
        $bot->sendMessage(__('prompt.homework.enter_description'));
        $this->next('promptDescription');
    }

    public function promptDescription(Nutgram $bot): void
    {
        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $bot->sendMessage(__('error.homework.description_empty'));
            $this->next('promptDescription');

            return;
        }

        $text = trim($input);

        if (mb_strlen($text) < self::MIN_DESCRIPTION_LENGTH) {
            $bot->sendMessage(__('error.homework.description_too_short', ['min' => self::MIN_DESCRIPTION_LENGTH]));
            $this->next('promptDescription');

            return;
        }

        $this->description = $text;

        $user = $this->getUser($bot);

        Homework::create([
            'class_id' => $user->class_id,
            'date' => $this->date,
            'description' => $this->description,
            'subject_id' => $this->subjectId,
        ]);

        $bot->sendMessage(__('info.homework.created'));
        $this->end();
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
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
