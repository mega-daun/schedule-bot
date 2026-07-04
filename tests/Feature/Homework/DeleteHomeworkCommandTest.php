<?php

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;

describe('DeleteHomework command', function () {
    it('prompts for date selection when user in a class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => __('prompt.homework.select_period'),
        ]);
        assertReplyMarkupContains($bot, [
            __('button_labels.keyboard.this_week'),
            'deletehomework.date.thisweek',
            __('button_labels.keyboard.next_week'),
            'deletehomework.date.nextweek',
            __('button_labels.keyboard.custom'),
            'deletehomework.date.custom',
        ]);
    });

    it('returns error when user not in any class', function () {
        $user = User::factory()->teacher()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();
        assertReplyContains($bot, __('error.homework.not_in_class'));
    });

    it('returns error when user is student', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        assertReplyContains($bot, __('error.homework.no_permission'));
    });

    it('allows on-duty role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => __('prompt.homework.select_period'),
        ]);
    });

    it('allows teacher role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => __('prompt.homework.select_period'),
        ]);
    });

    it('allows admin role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => __('prompt.homework.select_period'),
        ]);
    });

    it('returns error when user already in a conversation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();
    });
});

describe('DeleteHomework date selection', function () {
    it('rejects non-callback query at date selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data and prompts again', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('processes "this week" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            (new DateTime($homework->date))->format('d.m'),
        ]);
    });

    it('processes "next week" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.nextweek')->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            (new DateTime($homework->date))->format('d.m'),
        ]);
    });

    it('processes "custom" callback and prompts for date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        assertReplyContains($bot, __('prompt.homework.enter_date_format'));
        $bot->assertActiveConversation();
    });
});

describe('DeleteHomework custom date input', function () {
    it('prompts for text date when custom option selected', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        assertReplyContains($bot, __('prompt.homework.enter_date_format'));
        $bot->assertActiveConversation();
    });

    it('rejects callback query at date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearCallbackQueryData('some.callback')->reply();
        assertReplyContains($bot, __('prompt.general.enter_date_text'));
        $bot->assertActiveConversation();
    });

    it('rejects empty date input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, __('error.homework.date_empty'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid date format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText('not a date')->reply();
        assertReplyContains($bot, __('error.homework.date_invalid'));
        $bot->assertActiveConversation();
    });

    it('accepts YYYY-MM-DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('Y-m-d'))->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            now()->format('d.m'),
        ]);
    });

    it('accepts DD.MM.YYYY format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            now()->format('d.m'),
        ]);
    });

    it('accepts DD.MM format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m'))->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            now()->format('d.m'),
        ]);
    });

    it('accepts DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d'))->reply();
        $bot->assertActiveConversation();
        assertReplyContains($bot, __('prompt.homework.select_for_delete'));
        assertReplyMarkupContains($bot, [
            'deletehomework.select.'.$homework->id,
            now()->format('d.m'),
        ]);
    });
});

describe('DeleteHomework homework list display', function () {
    it('shows homeworks for this week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        assertReplyContains($bot, (new DateTime($homework->date))->format('d.m'));
        $bot->assertActiveConversation();
    });

    it('shows homeworks for next week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.nextweek')->reply();
        assertReplyContains($bot, (new DateTime($homework->date))->format('d.m'));
        $bot->assertActiveConversation();
    });

    it('shows homeworks for custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        assertReplyContains($bot, now()->format('d.m'));
        $bot->assertActiveConversation();
    });

    it('shows no homeworks message when none exist', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        assertReplyContains($bot, __('error.homework.none_in_period'));
        $bot->assertNoConversation();
    });

    it('does not show homeworks from other classes', function () {
        $class1 = Classroom::factory()->create();
        $class2 = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class1->id]);
        $subjectOtherClass = Subject::factory()->create(['class_id' => $class2->id]);
        $homeworkOtherClass = Homework::factory()->create([
            'class_id' => $class2->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subjectOtherClass->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        assertReplyMarkupNotContains($bot, [$homeworkOtherClass->description]);
    });

    it('shows homeworks only from current week in date range', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subjectThisWeek = Subject::factory()->create(['class_id' => $class->id]);
        $homeworkThisWeek = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subjectThisWeek->id,
        ]);
        $subjectNextWeek = Subject::factory()->create(['class_id' => $class->id]);
        $homeworkNextWeek = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'subject_id' => $subjectNextWeek->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        assertReplyContains($bot, (new DateTime($homeworkThisWeek->date))->format('d.m'));
        assertReplyMarkupNotContains($bot, [(new DateTime($homeworkNextWeek->date))->format('d.m')]);
    });
});

describe('DeleteHomework homework selection', function () {
    it('rejects non-callback query at homework selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data at homework selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('processes valid homework selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
        assertReplyContains($bot, __('info.homework.deleted'));
        $bot->assertNoConversation();
    });
});

describe('DeleteHomework deletion', function () {
    it('deletes selected homework from database', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
    });

    it('shows success message after deletion', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        assertReplyContains($bot, __('info.homework.deleted'));
    });

    it('conversation ends after deletion', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $bot->assertNoConversation();
    });

    it('handles already deleted homework gracefully', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();

        $homework->delete();

        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        assertReplyContains($bot, __('error.homework.not_found'));
        $bot->assertNoConversation();
    });
});

describe('DeleteHomework cancel and edge cases', function () {
    it('cancel works at date selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('cancel works at custom date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('cancel works at homework selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });
});

describe('DeleteHomework full conversation flow', function () {
    it('complete flow with this week selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
        assertReplyContains($bot, __('info.homework.deleted'));
        $bot->assertNoConversation();
    });

    it('complete flow with next week selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletehomework.date.nextweek')->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
        assertReplyContains($bot, __('info.homework.deleted'));
        $bot->assertNoConversation();
    });

    it('complete flow with custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
        assertReplyContains($bot, __('info.homework.deleted'));
        $bot->assertNoConversation();
    });

    it('complete flow with no homeworks in range', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletehomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();

        assertReplyContains($bot, __('error.homework.none_in_period'));
        $bot->assertNoConversation();
    });

    it('complete flow when homework deleted concurrently', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/deletehomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('deletehomework.date.thisweek')->reply();
        $bot->assertActiveConversation();

        $homework->delete();

        $bot->hearCallbackQueryData('deletehomework.select.'.$homework->id)->reply();

        assertReplyContains($bot, __('error.homework.not_found'));
        $bot->assertNoConversation();
    });
});
