<?php

declare(strict_types=1);

namespace App\BotCommands\Conversations;

use App\Enums\UserRole;
use App\Models\User;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ChangeRoleConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);

        if (! $user->class) {
            $bot->sendMessage('Вы должны состоять в классе.');
            $this->end();

            return;
        }

        if ($user->role !== UserRole::Admin) {
            $bot->sendMessage('Только админы могут изменять роли других пользователей.');
            $this->end();

            return;
        }

        $classMembers = User::where('class_id', $user->class_id)
            ->where('id', '!=', $user->id)
            ->get();

        if ($classMembers->isEmpty()) {
            $bot->sendMessage('Нет других участников');
            $this->end();

            return;
        }

        $keyboard = $this->buildUserKeyboard($classMembers);

        $bot->sendMessage(
            text: 'Выберите пользователя',
            reply_markup: $keyboard,
        );

        $this->next('handleUserSelection');
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
            return;
        }

        $selectedUserId = (int) str_replace('changerole.select.', '', $callbackData);

        $targetUser = User::find($selectedUserId);

        if (! $targetUser) {
            $bot->answerCallbackQuery('Пользователь не найден');
            $bot->sendMessage('Пользователь не найден');
            $this->end();

            return;
        }

        $keyboard = $this->buildRoleKeyboard($selectedUserId);

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
            return;
        }

        $roleKey = str_replace('changerole.role.', '', $callbackData);

        if (str_contains($roleKey, '_')) {
            $parts = explode('_', $roleKey);
            $roleKey = $parts[0];
            $selectedUserId = (int) $parts[1];
        } else {
            $selectedUserId = null;
        }

        $role = UserRole::tryFrom($roleKey);

        if ($role === null) {
            $bot->answerCallbackQuery('Неверная роль');

            return;
        }

        if ($selectedUserId === null) {
            $bot->sendMessage('Ошибка: пользователь не выбран');
            $this->end();

            return;
        }

        $admin = $this->getUser($bot);

        if ($selectedUserId === $admin->id) {
            $bot->answerCallbackQuery('Нельзя изменить роль самого себя');
            $bot->sendMessage('Нельзя изменить роль самого себя');
            $this->end();

            return;
        }

        $targetUser = User::find($selectedUserId);

        if (! $targetUser) {
            $bot->sendMessage('Пользователь не найден');
            $this->end();

            return;
        }

        $targetUser->update(['role' => $role]);

        $bot->sendMessage('Роль изменена на '.$role->value);

        $this->end();
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

    private function getUser(Nutgram $bot): User
    {
        $telegramUser = $bot->user();

        return User::findOrFail($telegramUser->id);
    }
}
