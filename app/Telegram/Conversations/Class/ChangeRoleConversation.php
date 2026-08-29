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
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ParserService $parser) {}

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);

        $classMembers = $this->findClassmembers($user->class_id, $user->id);

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'changerole.select',
            $classMembers,
            fn (User $member) => $member->first_name,
            fn (User $member) => $member->id
        );

        $bot->sendMessage(
            text: __('prompt.role.select_user'),
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
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('handleUserSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'changerole.select.')) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('handleUserSelection');

            return;
        }

        $selectedUserId = (int) $this->parser->parseCallbackData($callbackData);

        $roles = collect([
            ['text' => __('button_labels.role.student'), 'data' => 'ученик_'.$selectedUserId],
            ['text' => __('button_labels.role.teacher'), 'data' => 'учитель_'.$selectedUserId],
            ['text' => __('button_labels.role.onduty'), 'data' => 'дежурный_'.$selectedUserId],
            ['text' => __('button_labels.role.admin'), 'data' => 'админ_'.$selectedUserId],
        ]);

        $keyboard = $this->keyboardGenerator->buildSelectionKeyboard(
            'changerole.role',
            $roles,
            fn ($role) => $role['text'],
            fn ($role) => $role['data']
        );

        $bot->sendMessage(__('prompt.role.select_role'),
            reply_markup: $keyboard,
        );

        $this->next('handleRoleSelection');
    }

    public function handleRoleSelection(Nutgram $bot)
    {
        if (! $bot->isCallbackQuery()) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('handleRoleSelection');

            return;
        }

        $callbackData = $bot->callbackQuery()->data;

        if (! str_starts_with($callbackData, 'changerole.role.')) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('handleRoleSelection');

            return;
        }

        $value = $this->parser->parseCallbackData($callbackData);

        if (! str_contains($value, '_')) {
            $bot->sendMessage(__('prompt.general.click_button'));
            $this->next('handleRoleSelection');

            return;
        }

        [$role, $selectedUserId] = explode('_', $value, 2);
        $selectedUserId = (int) $selectedUserId;

        $admin = $this->getUser($bot);

        if ($selectedUserId === $admin->id) {
            $bot->answerCallbackQuery(__('error.role.self_change'));
            $bot->sendMessage(__('error.role.self_change'));
            $this->end();

            return;
        }

        $targetUser = $this->findUser($selectedUserId);
        try {
            $targetUser->changeRole($role);
        } catch (UnknownRoleException) {
            $bot->answerCallbackQuery(__('error.role.invalid_short'));
        }

        $bot->sendMessage(__('info.role.changed_to', ['role' => $role]));

        $this->end();
    }

    private function findUser(int $id): User
    {
        $targetUser = User::find($id);

        if (! $targetUser) {
            throw new IncorrectMessageException(__('error.role.user_not_found'), true);
        }

        return $targetUser;
    }
}
