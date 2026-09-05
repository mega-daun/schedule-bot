<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Class;

use App\Actions\Class\ChangeRoleAction;
use App\Helpers\MessageKeyboardGenerator;
use App\Models\User;
use App\Telegram\Conversations\BaseConversation;
use SergiX44\Nutgram\Nutgram;

class ChangeRoleConversation extends BaseConversation
{
    public function __construct(private MessageKeyboardGenerator $keyboardGenerator, private ChangeRoleAction $changeRoleAction) {}

    public function start(Nutgram $bot)
    {
        $user = $this->getUser($bot);
        $classMembers = $this->changeRoleAction->getClassMembers($user->class_id, $user->id);

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

    public function handleUserSelection(Nutgram $bot)
    {
        $selectedUserId = $this->getCallbackAnswerOrError($bot, 'changerole.select', 'handleUserSelection');

        if ($selectedUserId === false) {
            return;
        }

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
        $value = $this->getCallbackAnswerOrError($bot, 'changerole.role', 'handleRoleSelection');

        if ($value === false) {
            return;
        }

        if (! str_contains($value, '_')) {
            $this->errorAndProceed(__('prompt.general.click_button'), 'handleRoleSelection');

            return;
        }

        [$role, $selectedUserId] = explode('_', $value, 2);
        $selectedUserId = (int) $selectedUserId;

        $admin = $this->getUser($bot);

        if ($selectedUserId === $admin->id) {
            $this->errorAndEnd(__('error.role.self_change'));

            return;
        }

        try {
            $targetUser = $this->changeRoleAction->findUser($selectedUserId);
        } catch (\InvalidArgumentException $e) {
            $this->errorAndEnd($e->getMessage());

            return;
        }

        if ($targetUser->class_id !== $admin->class_id) {
            $this->errorAndEnd(__('error.role.not_in_class'));

            return;
        }

        $this->changeRoleAction->changeRole($targetUser, $role);

        $this->replyAndEnd($bot, __('info.role.changed_to', ['role' => $role]));
    }
}
