<?php

declare(strict_types=1);

namespace App\BotCommands\Subject;

use App\Models\Subject;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class DeleteSubjectConversation extends Conversation
{
    private ?int $selectedSubjectId = null;

    public function start(Nutgram $bot)
    {
        $user = $this->getUser();

        $subjects = $user->class->subjects;

        if ($subjects->isEmpty()) {
            $bot->sendMessage(text: 'В классе нет предметов для удаления.');
            $this->end();

            return;
        }

        $keyboard = $this->buildSubjectKeyboard($subjects);

        $bot->sendMessage(
            text: 'Выберите предмет для удаления:',
            reply_markup: $keyboard
        );
        $this->next('handleSelection');
    }

    public function handleSelection(Nutgram $bot)
    {
        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'deletesubject.select.')) {
            $bot->answerCallbackQuery(text: 'Неверный формат. Выберите предмет из списка.');

            return;
        }

        $this->selectedSubjectId = (int) substr($callbackData, strlen('deletesubject.select.'));

        $subject = Subject::find($this->selectedSubjectId);

        if (! $subject) {
            $bot->answerCallbackQuery(text: 'Предмет не найден.');
            $this->end();

            return;
        }

        $subjectName = $subject->name;
        $subject->delete();

        $bot->answerCallbackQuery(text: 'Предмет "'.$subjectName.'" удалён.');

        $bot->sendMessage(text: 'Предмет "'.$subjectName.'" успешно удалён.');

        $this->end();
    }

    private function getUser(): User
    {
        $telegramUser = $this->bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function buildSubjectKeyboard($subjects): InlineKeyboardMarkup
    {
        $buttons = [];

        foreach ($subjects as $subject) {
            $buttons[] = InlineKeyboardButton::make(
                text: $subject->name,
                callback_data: 'deletesubject.select.'.$subject->id
            );
        }

        $markup = InlineKeyboardMarkup::make();
        foreach (array_chunk($buttons, 2) as $row) {
            $markup->addRow($row);
        }

        return $markup;
    }
}
