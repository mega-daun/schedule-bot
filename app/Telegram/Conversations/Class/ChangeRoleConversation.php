<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\BotCommands\Exceptions\IncorrectMessageException;
use App\Exceptions\UnknownRoleException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ChangeRoleConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $this->bot = $bot;
        $user = $this->getUser();

        $classMembers = $this->findClassmembers($user->class_id, $user->id);

        $keyboard = $this->buildUserKeyboard($classMembers);

        $bot->sendMessage(
            text: 'Выберите пользователя',
            reply_markup: $keyboard,
        );

        $this->next('handleUserSelection');
    }

    private function getUser(): User
    {
        $telegramUser = $this->bot->user();

        return User::findOrFail($telegramUser->id);
    }

    private function findClassmembers(int $class_id, int $caller_id): Collection
    {
        return User::where('class_id', $class_id)
            ->where('id', '!=', $caller_id)
            ->get();
    }

    private function buildUserKeyboard($classMembers): InlineKeyboardMarkup
    {
        $buttons = [];

        foreach ($classMembers as $member) {
            $username = $member->username ?? 'Без имени';
            $buttons[] = InlineKeyboardButton::make(
                text: '@'.$username,
                callback_data: 'changerole.select.'.$member->id
            );
        }

        $markup = InlineKeyboardMarkup::make();
        foreach (array_chunk($buttons, 2) as $btns) {
            $markup->addRow($btns);
        }

        return $markup;
    }

    public function handleUserSelection(Nutgram $bot)
    {
        $this->bot = $bot;
        try {
            $callbackData = $this->getSelectedOption();
        } catch (IncorrectMessageException) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleUserSelection');

            return;
        }

        $selectedUserId = $this->parseUserId($callbackData);

        $keyboard = $this->buildRoleKeyboard($selectedUserId);

        $bot->sendMessage('Выберите роль',
            reply_markup: $keyboard,
        );

        $this->next('handleRoleSelection');
    }

    private function getSelectedOption(): string
    {
        if (! $this->bot->isCallbackQuery()) {
            throw new IncorrectMessageException;
        }

        return $this->bot->callbackQuery()->data;
    }

    private function parseUserId(string $callbackData): int
    {
        if (! str_starts_with($callbackData, 'changerole.select.')) {
            // don't catch it because it happens when our callback query is wrong
            throw new IncorrectMessageException;
        }

        return (int) str_replace('changerole.select.', '', $callbackData);

    }

    public function handleRoleSelection(Nutgram $bot)
    {
        try {
            $callbackData = $this->getSelectedOption();
        } catch (IncorrectMessageException) {
            $bot->sendMessage('Нажмите на кнопку или введите /cancel для отмены.');
            $this->next('handleRoleSelection');

            return;
        }

        [$role, $selectedUserId] = $this->parseRoleAndUserId($callbackData);

        $admin = $this->getUser();

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

    private function parseRoleAndUserId(string $callbackData): array
    {
        if (! str_starts_with($callbackData, 'changerole.role.')) {
            throw new IncorrectMessageException;
        }

        $role = str_replace('changerole.role.', '', $callbackData);

        if (str_contains($role, '_')) {
            $parts = explode('_', $role);
            $role = $parts[0];
            $userId = (int) $parts[1];
        } else {
            throw new IncorrectMessageException;
        }

        return [$role, $userId];
    }

    private function findUser(int $id): User
    {
        $targetUser = User::find($id);

        if (! $targetUser) {
            throw new IncorrectMessageException('Пользователь не найден', true);
        }

        return $targetUser;
    }

    private function buildRoleKeyboard(int $selectedUserId): InlineKeyboardMarkup
    {
        $buttons = [
            InlineKeyboardButton::make(
                text: 'ученик',
                callback_data: 'changerole.role.ученик_'.$selectedUserId
            ),
            InlineKeyboardButton::make(
                text: 'учитель',
                callback_data: 'changerole.role.учитель_'.$selectedUserId
            ),
            InlineKeyboardButton::make(
                text: 'дежурный',
                callback_data: 'changerole.role.дежурный_'.$selectedUserId
            ),
            InlineKeyboardButton::make(
                text: 'админ',
                callback_data: 'changerole.role.админ_'.$selectedUserId
            ),
        ];

        $markup = InlineKeyboardMarkup::make();
        foreach (array_chunk($buttons, 2) as $btns) {
            $markup->addRow($btns);
        }

        return $markup;
    }
}
