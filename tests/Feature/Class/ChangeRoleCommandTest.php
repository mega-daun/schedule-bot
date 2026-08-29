<?php

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;

describe('ChangeRoleCommand', function () {
    describe('Change role via arguments /changerole {username} {role}', function () {
        it('admin can change user role to Teacher via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'учитель'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser учитель')->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Teacher, $targetUser->role);
        });

        it('admin can change user role to Student via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->teacher()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'ученик'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser ученик')->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Student, $targetUser->role);
        });

        it('admin can change user role to OnDuty via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'дежурный'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser дежурный')->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::OnDuty, $targetUser->role);
        });

        it('admin can change user role to Admin via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'админ'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser админ')->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Admin, $targetUser->role);
        });

        it('returns success message after role change via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'учитель'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser учитель')->reply();
            assertReplyContains($bot, __('info.role.changed'));
        });

        it('persists role change to database via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'учитель'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser учитель')->reply();
            $this->assertDatabaseHas('users', [
                'id' => $targetUser->id,
                'role' => UserRole::Teacher,
            ]);
        });

        it('admin cannot change own role via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id, 'username' => 'adminuser']);
            $bot = botWithData(['username' => 'adminuser', 'role' => 'ученик'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole adminuser ученик')->reply();
            $admin->refresh();
            $this->assertEquals(UserRole::Admin, $admin->role);
            assertReplyContains($bot, __('error.role.self_change'));
        });

        it('shows error for invalid role via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = botWithData(['username' => 'targetuser', 'role' => 'неверная роль'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole targetuser неверная роль')->reply();
            assertReplyContains($bot, __('error.role.invalid'));
        });

        it('shows error for non-existent user via arguments', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $bot = botWithData(['username' => 'nonexistentuser', 'role' => 'учитель'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole nonexistentuser учитель')->reply();
            assertReplyContains($bot, __('error.role.user_not_found'));
        });

        it('shows error when target user is not in same class', function () {
            $classroom = Classroom::factory()->create();
            $otherClassroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            User::factory()->student()->create(['class_id' => $otherClassroom->id, 'username' => 'otheruser']);
            $bot = botWithData(['username' => 'otheruser', 'role' => 'учитель'], $admin->id, $admin->first_name);
            $bot->hearText('/changerole otheruser учитель')->reply();
            assertReplyContains($bot, __('error.role.not_in_class'));
        });
    });

    describe('User is not admin - rejects', function () {
        it('non-admin receives error message', function () {
            $classroom = Classroom::factory()->has(User::factory(3))->create();
            $user = User::factory()->student()->create(['class_id' => $classroom->id]);
            $bot = bot($user);
            $bot->hearText('/changerole')->reply();
            assertReplyContains($bot, __('error.class.no_permission'));
        });

        it('teacher cannot access changerole command', function () {
            $classroom = Classroom::factory()->has(User::factory(3))->create();
            $user = User::factory()->teacher()->create(['class_id' => $classroom->id]);
            $bot = bot($user);
            $bot->hearText('/changerole')->reply();
            assertReplyContains($bot, __('error.class.no_permission'));
        });

        it('onDuty cannot access changerole command', function () {
            $classroom = Classroom::factory()->has(User::factory(3))->create();
            $user = User::factory()->onDuty()->create(['class_id' => $classroom->id]);
            $bot = bot($user);
            $bot->hearText('/changerole')->reply();
            assertReplyContains($bot, __('error.class.no_permission'));
        });
    });

    describe('Without class - rejects', function () {
        it('Student without class receives error message', function () {
            $student = User::factory()->student()->create(['class_id' => null]);
            $bot = bot($student);
            $bot->hearText('/changerole')->reply();
            assertReplyContains($bot, __('error.class.not_member'));
        });

        it('Admin without class receives error message', function () {
            $admin = User::factory()->admin()->create(['class_id' => null]);
            $bot = bot($admin);
            $bot->hearText('/changerole')->reply();
            assertReplyContains($bot, __('error.class.not_member'));
        });
    });

    describe('User is admin - starts conversation and shows user selection', function () {
        it('admin starts conversation when calling /changerole', function () {
            $classroom = Classroom::factory()->has(User::factory(3))->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->assertActiveConversation();
        });

        it('admin sees inline keyboard with class members', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'member1', 'first_name' => 'Maria']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->assertReplyMessage([
                'text' => __('prompt.role.select_user'),
            ]);
            assertReplyContains($bot, 'Maria');
        });

        it('inline keyboard contains callback data for each member', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'member1']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return str_contains($body, 'changerole.select.');
            });
        });

        it('admin sees message to select user', function () {
            $classroom = Classroom::factory()->has(User::factory(3))->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            assertReplyContains($bot, __('prompt.role.select_user'));
        });

        it('shows only class members in keyboard', function () {
            $classroom = Classroom::factory()->create();
            $otherClassroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'member1', 'first_name' => 'Maria']);
            User::factory()->student()->create(['class_id' => $otherClassroom->id, 'username' => 'othermember', 'first_name' => 'Other']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            assertReplyContains($bot, 'Maria');
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return ! str_contains($body, 'Other');
            });
        });

        it('shows message when class has no other members', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            assertReplyContains($bot, __('error.class.no_members'));
            $bot->assertNoConversation($admin->id, $admin->id);
        });
    });

    describe('ChangeRole conversation - user selection', function () {
        it('shows role selection after user is selected via callback', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->assertActiveConversation();
        });

        it('prompts to enter role after user selection', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            assertReplyContains($bot, __('prompt.role.select_role'));
        });

        it('shows role options in message', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            assertReplyContains($bot, __('button_labels.role.student'));
            assertReplyContains($bot, __('button_labels.role.teacher'));
            assertReplyContains($bot, __('button_labels.role.onduty'));
            assertReplyContains($bot, __('button_labels.role.admin'));
        });
    });

    describe('ChangeRole conversation - role change', function () {
        it('admin can change user role to Teacher via callback', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.учитель_'.$targetUser->id)->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Teacher, $targetUser->role);
        });

        it('admin can change user role to Student via callback', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->teacher()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.ученик_'.$targetUser->id)->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Student, $targetUser->role);
        });

        it('admin can change user role to OnDuty via callback', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.дежурный_'.$targetUser->id)->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::OnDuty, $targetUser->role);
        });

        it('admin can change user role to Admin via callback', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.админ_'.$targetUser->id)->reply();
            $targetUser->refresh();
            $this->assertEquals(UserRole::Admin, $targetUser->role);
        });

        it('returns success message after role change', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.учитель_'.$targetUser->id)->reply();
            assertReplyContains($bot, __('info.role.changed'));
        });

        it('persists role change to database', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.учитель_'.$targetUser->id)->reply();
            $this->assertDatabaseHas('users', [
                'id' => $targetUser->id,
                'role' => UserRole::Teacher,
            ]);
        });

        it('ends conversation after role change', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.учитель_'.$targetUser->id)->reply();
            $bot->assertNoConversation();
        });

        it('shows inline keyboard with role options after user selection', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return str_contains($body, 'changerole.role.');
            });
        });

        it('shows error when selected user is deleted before role selection', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $targetUserId = $targetUser->id;
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUserId)->reply();
            $targetUser->delete();
            $bot->hearCallbackQueryData('changerole.role.учитель_'.$targetUser->id)->reply();
            assertReplyContains($bot, __('error.role.user_not_found'));
        });
    });

    describe('Admin cannot change own role', function () {
        it('admin cannot change their own role', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id, 'username' => 'adminuser']);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'otheruser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$admin->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.ученик_'.$admin->id)->reply();
            assertReplyContains($bot, __('error.role.self_change'));
        });

        it('admin role remains unchanged after trying to change own role', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id, 'username' => 'adminuser']);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'otheruser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$admin->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.ученик_'.$admin->id)->reply();
            $admin->refresh();
            $this->assertEquals(UserRole::Admin, $admin->role);
        });

        it('ends conversation after trying to change own role', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id, 'username' => 'adminuser']);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'otheruser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$admin->id)->reply();
            $bot->hearCallbackQueryData('changerole.role.ученик_'.$admin->id)->reply();
            $bot->assertNoConversation();
        });
    });

    describe('Role selection inline keyboard', function () {
        it('role keyboard shows all available roles', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->assertReplyMessage([
                'text' => __('prompt.role.select_role'),
            ]);
        });

        it('role keyboard contains all four roles', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return str_contains($body, 'ученик')
                    && str_contains($body, 'учитель')
                    && str_contains($body, 'дежурный')
                    && str_contains($body, 'админ');
            });
        });

        it('role keyboard contains correct callback data for all roles', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return str_contains($body, 'changerole.role.ученик_')
                    && str_contains($body, 'changerole.role.учитель_')
                    && str_contains($body, 'changerole.role.дежурный_')
                    && str_contains($body, 'changerole.role.админ_');
            });
        });

        it('user keyboard does not show users without class', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'member1', 'first_name' => 'Maria']);
            User::factory()->student()->create(['class_id' => null, 'username' => 'noClassUser', 'first_name' => 'NoClass']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            assertReplyContains($bot, 'Maria');
            $bot->assertRaw(function ($request) {
                $body = (string) $request->getBody();

                return ! str_contains($body, 'NoClass');
            });
        });
    });

    describe('Invalid input during conversation', function () {
        it('shows error message when user sends text instead of clicking button', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearText('любой текст')->reply();
            assertReplyContains($bot, __('prompt.general.click_button'));
            assertReplyContains($bot, '/cancel');
        });

        it('conversation remains active after wrong text input', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearText('любой текст')->reply();
            $bot->assertActiveConversation();
        });

        it('ends conversation when user sends /cancel', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearText('/cancel')->reply();
            $bot->assertNoConversation();
        });

        it('returns cancel confirmation when user sends /cancel', function () {
            $classroom = Classroom::factory()->create();
            $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
            $targetUser = User::factory()->student()->create(['class_id' => $classroom->id, 'username' => 'targetuser']);
            $bot = bot($admin);
            $bot->willStartConversation(remember: true)
                ->hearText('/changerole')
                ->reply();
            $bot->hearCallbackQueryData('changerole.select.'.$targetUser->id)->reply();
            $bot->hearText('/cancel')->reply();
            assertReplyContains($bot, __('error.cancel.no_active'));
        });
    });
});
