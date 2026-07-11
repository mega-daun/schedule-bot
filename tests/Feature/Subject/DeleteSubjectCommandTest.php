<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('DeleteSubject command', function () {
    it('starts conversation and shows subject list', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.select_for_delete'));
        assertReplyMarkupContains($bot, [$subject->name]);
        $bot->assertActiveConversation();
    });

    it('returns error when user is not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('error.class.not_member'));
    });

    it('returns error when user has Student role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
    });

    it('allows Teacher role to start conversation', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.select_for_delete'));
        $bot->assertActiveConversation();
    });

    it('allows OnDuty role to start conversation', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.select_for_delete'));
        $bot->assertActiveConversation();
    });

    it('returns error when class has no subjects', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        assertReplyContains($bot, __('error.class.no_subjects'));
    });

    it('returns error when user is already in a conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->assertActiveConversation();
    });
});

describe('DeleteSubject conversation validation', function () {
    it('rejects non-callback query at selection step', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects subject from another class', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $otherClass = Classroom::factory()->create();
        $otherSubject = Subject::factory()->create(['class_id' => $otherClass->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->hearCallbackQueryData('deletesubject.select.'.$otherSubject->id)->reply();
        assertReplyContains($bot, __('error.subject.not_found'));
        $bot->assertActiveConversation();
    });

    it('shows subject list with correct subjects', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        assertReplyMarkupContains($bot, ['Mathematics', 'Physics', (string) $subject1->id, (string) $subject2->id]);
        $bot->assertActiveConversation();
    });

    it('successfully deletes selected subject', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->hearCallbackQueryData('deletesubject.select.'.$subject->id)->reply();
        assertReplyContains($bot, __('info.subject.deleted'));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
        $bot->assertNoConversation();
    });

    it('cancel works at selection step', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();

        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });
});

describe('DeleteSubject full conversation flow', function () {
    it('completes full flow with valid selection', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletesubject.select.'.$subject->id)->reply();
        assertReplyContains($bot, __('info.subject.deleted', ['name' => 'Mathematics']));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
        $bot->assertNoConversation();
    });

    it('cancel works at selection step in full flow', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletesubject')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });
});
