<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Exceptions\IncorrectMessageException;
use App\Exceptions\UnknownRoleException;
use App\Helpers\MessageKeyboardGenerator;
use App\Helpers\ParserService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class ChangeRoleConversation extends Conversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser)
    {
    }

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);

        $classMembers = $this->findClassmembers($user->class_id, $user->id);

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'changerole.select',
            $classMembers,
            fn (User $member) => '@'.($member->username ?? 'Без имени'),
            fn (User $member) => $member->id
        );

        $bot->sendMessage(
            text: 'Выберите пользователя',
            reply_markup: $keyboard,
        );

        $this->next('handleUserSelection');
    }

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function findClassmembers(int $class_id, int $caller_id): Collection
    {
        return User::where('class_id', $class_id)
            ->where('id', '!=', $caller_id)
            ->get();
    }

    public function handleUserSelection(Nutgram $bot)
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleUserSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'changerole.select.')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleUserSelection');

            return;
        }

        $selectedUserId = (int) $this->parser->parseCallbackData($callbackData);

        $roles = collect([
            ['text' => 'ученик', 'data' => 'ученик_'.$selectedUserId],
            ['text' => 'учитель', 'data' => 'учитель_'.$selectedUserId],
            ['text' => 'дежурный', 'data' => 'дежурный_'.$selectedUserId],
            ['text' => 'админ', 'data' => 'админ_'.$selectedUserId],
        ]);

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'changerole.role',
            $roles,
            fn ($role) => $role['text'],
            fn ($role) => $role['data']
        );

        $bot->sendMessage('Выберите роль',
            reply_markup: $keyboard,
        );

        $this->next('handleRoleSelection');
    }

    public function handleRoleSelection(Nutgram $bot)
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleRoleSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'changerole.role.')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleRoleSelection');

            return;
        }

        $value = $this->parser->parseCallbackData($callbackData);

        if (! str_contains($value, '_')) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleRoleSelection');

            return;
        }

        [$role, $selectedUserId] = explode('_', $value, 2);
        $selectedUserId = (int) $selectedUserId;

        $admin = $this->getUser($bot);

        if ($selectedUserId === $admin->id) {
            $bot->answerCallbackQuery('Нельзя изменить роль самого себя');
            $bot->sendMessage('Нельзя изменить роль самого себя');
            $this->end();

            return;
        }

        $targetUser = $this->findUser($selectedUserId);
        try {
            $targetUser->changeRole($role);
        } catch (UnknownRoleException) {
            $bot->answerCallbackQuery('Неверная роль');
        }

        $bot->sendMessage('Роль изменена на '.$role);

        $this->end();
    }

    private function findUser(int $id): User
    {
        $targetUser = User::find($id);

        if (! $targetUser) {
            throw new IncorrectMessageException('Пользователь не найден', true);
        }

        return $targetUser;
    }
}
