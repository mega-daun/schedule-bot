<?php

namespace App\Telegram\Conversations\Subject;

use App\Actions\Subject\DeleteSubjectAction;
use App\Exceptions\IncorrectMessageException;
use App\Exceptions\InvalidInputException;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\ParserService;
use App\Models\Subject;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class DeleteSubjectConversation extends Conversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser) {}

    public ?int $class_id = null;

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $subjects = $user->class->subjects;
        $this->class_id = (int) $user->class_id;
        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard('deletesubject.select', $subjects, fn (Subject $s) => $s->name, fn (Subject $s) => $s->id);
        $bot->sendMessage(text: __('prompt.subject.select_for_delete'), reply_markup: $keyboard);
        $this->next('handleSelection');
    }

    public function handleSelection(Nutgram $bot)
    {
        if (! $subjectId = $this->validateCallbackData($bot, 'deletesubject.select', 'handleSelection')) {
            return;
        }
        try {
            $action = new DeleteSubjectAction((int) $subjectId, $this->class_id);
            $action();
        } catch (InvalidInputException $e) {
            $this->next('handleSelection');
            throw new IncorrectMessageException($e->getMessage().__('error.try_again'));
        }
        $bot->sendMessage(text: __('info.subject.deleted'));
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
