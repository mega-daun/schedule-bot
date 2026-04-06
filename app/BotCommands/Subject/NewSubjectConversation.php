<?php

declare(strict_types=1);

namespace App\BotCommands\Subject;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Models\Subject;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class NewSubjectConversation extends Conversation
{
    private const MAX_NAME_LENGTH = 50;

    public function start(Nutgram $bot)
    {
        $bot->sendMessage(
            text: 'Введите название предмета:'
        );
        $this->next('handleInput');
    }

    public function handleInput(Nutgram $bot)
    {
        $subjectName = trim($bot->message()->text);

        $this->validateSubjectName($subjectName);

        $user = $this->getUser();

        $this->checkIfSubjectAlreadyExists($subjectName, $user->class_id);

        $subject = Subject::create([
            'class_id' => $user->class_id,
            'name' => $subjectName,
        ]);

        $bot->sendMessage(
            text: 'Предмет "'.$subject->name.'" успешно добавлен.'
        );

        $this->end();
    }

    private function validateSubjectName(string $subjectName)
    {
        if ($subjectName === '') {
            throw new IncorrectMessageException('Название не может быть пустым. Введите название предмета:');
        }

        if (mb_strlen($subjectName) > self::MAX_NAME_LENGTH) {
            throw new IncorrectMessageException('Название слишком длинное. Максимум '.self::MAX_NAME_LENGTH.' символов. Введите название предмета:');
        }
    }

    private function checkIfSubjectAlreadyExists(string $subjectName, int $classId)
    {
        $exists = Subject::where('class_id', $classId)
            ->where('name', $subjectName)
            ->exists();

        if ($exists) {
            throw new IncorrectMessageException('Предмет "'.$subjectName.'" уже существует. Введите другое название:');
        }
    }

    private function getUser(): User
    {
        $telegramUser = $this->bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
