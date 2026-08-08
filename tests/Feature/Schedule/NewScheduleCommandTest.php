<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('NewSchedule access control', function () {
    it('returns error when user not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('error.class.not_member'));
    });

    it('returns error when user has Student role', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
    });

    it('returns error when there are no subjects in class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('error.class.no_subjects'));
    });

    it('allows OnDuty role to start conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_weekdays'));
        $bot->assertActiveConversation();
    });

    it('allows Teacher role to start conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_weekdays'));
        $bot->assertActiveConversation();
    });

    it('allows Admin role to start conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_weekdays'));
        $bot->assertActiveConversation();
    });

    it('returns error when user already in a conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();

        $bot->assertActiveConversation();
    });
});

describe('Weekday selection menu', function () {
    it('shows weekday selection menu after /newschedule', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_weekdays'));
        $bot->assertActiveConversation();
    });

    it('menu contains all 7 weekday buttons', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyMarkupContains($bot, [
            __('general.weekday.1'),
            __('general.weekday.2'),
            __('general.weekday.3'),
            __('general.weekday.4'),
            __('general.weekday.5'),
            __('general.weekday.6'),
            __('general.weekday.7'),
        ]);
        $bot->assertActiveConversation();
    });

    it('menu contains Готово button', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyMarkupContains($bot, ['Готово']);
        $bot->assertActiveConversation();
    });

    it('callback data format is correct for weekdays', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyMarkupContains($bot, [
            'newschedule.weekday.add.1',
            'newschedule.weekday.add.2',
            'newschedule.weekday.add.3',
            'newschedule.weekday.add.4',
            'newschedule.weekday.add.5',
            'newschedule.weekday.add.6',
            'newschedule.weekday.add.7',
        ]);
        $bot->assertActiveConversation();
    });

    it('invalid callback shows error', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('non-callback at weekday selection step shows error', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });
});

describe('Weekday marking and unmarking', function () {
    it('clicking unmarked weekday marks it with checkmark', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        assertReplyMarkupContains($bot, [__('general.weekday.1').'✅']);
        $bot->assertActiveConversation();
    });

    it('clicking marked weekday unmarks it', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.remove.1')->reply();
        assertReplyMarkupNotContains($bot, [__('general.weekday.1').'✅']);
        $bot->assertActiveConversation();
    });

    it('multiple weekdays can be marked', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.5')->reply();
        assertReplyMarkupContains($bot, [
            __('general.weekday.1').'✅',
            __('general.weekday.3').'✅',
            __('general.weekday.5').'✅',
        ]);
        $bot->assertActiveConversation();
    });

    it('marking does not show order numbers', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.2')->reply();
        assertReplyMarkupNotContains($bot, ['(1)', '(2)']);
        assertReplyMarkupContains($bot, [
            __('general.weekday.1').'✅',
            __('general.weekday.2').'✅',
        ]);
        $bot->assertActiveConversation();
    });

    it('menu shows current state after each toggle', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        assertReplyMarkupContains($bot, [__('general.weekday.1').'✅']);
        assertReplyMarkupNotContains($bot, [__('general.weekday.2').'✅']);
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        assertReplyMarkupContains($bot, [
            __('general.weekday.1').'✅',
            __('general.weekday.3').'✅',
        ]);
        assertReplyMarkupNotContains($bot, [__('general.weekday.2').'✅']);
        $bot->assertActiveConversation();
    });
});

describe('Weekday selection completion', function () {
    it('clicking Готово with marked weekdays begins creation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->assertActiveConversation();
    });

    it('clicking Готово with no marked weekdays shows error', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.should_have_workdays'));
        $bot->assertActiveConversation();
    });

    it('marked weekdays are processed in ascending order', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.5')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->assertActiveConversation();
    });
});

describe('Lesson selection per weekday', function () {
    it('shows creating schedule message with correct weekday', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->assertActiveConversation();
    });

    it('menu contains all subjects as buttons', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $subject3 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Chemistry']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyMarkupContains($bot, ['Mathematics', 'Physics', 'Chemistry'], 1);
        $bot->assertActiveConversation();
    });

    it('menu contains Готово button', function () {
        $class = Classroom::factory()->create();
        $s = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$s->id)->reply();
        assertReplyMarkupContains($bot, ['Готово'], 0);
        $bot->assertActiveConversation();
    });

    it('selecting subject shows fresh menu for next lesson', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics']);
        assertReplyMarkupNotContains($bot, ['Mathematics✅']);
        $bot->assertActiveConversation();
    });

    it('same subject can be selected for multiple lessons', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, '1. Mathematics', 0);
        assertReplyContains($bot, '2. Mathematics', 0);
        $bot->assertActiveConversation();
    });

    it('Готово with no subjects goes to confirmation with no lessons', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.no_lessons'));
        $bot->assertActiveConversation();
    });

    it('Готово with subjects shows numbered list preview', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, '1. Mathematics');
        assertReplyContains($bot, '2. Physics');
        $bot->assertActiveConversation();
    });

    it('invalid callback at lesson selection shows error', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('invalid.data')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });
});

describe('Per-weekday confirmation', function () {
    it('shows preview of this weekdays schedule', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.schedule_preview', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->assertActiveConversation();
    });

    it('confirmation buttons are Да and Нет', function () {
        $class = Classroom::factory()->create();
        $s = Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$s->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyMarkupContains($bot, ['Да', 'Нет'], 1);
        $bot->assertActiveConversation();
    });

    it('clicking Да moves to next marked weekday', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.3'))]));
        $bot->assertActiveConversation();
    });

    it('clicking Нет clears current weekday and restarts lesson selection', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.no')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->assertActiveConversation();
    });

    it('after Нет, previous selections are forgotten', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.no')->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.no_lessons'));
        $bot->assertActiveConversation();
    });
});

describe('Weekday progression', function () {
    it('processes marked weekdays in ascending order skipping unmarked', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.5')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.1'))]));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.3'))]));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.5'))]));
        $bot->assertActiveConversation();
    });

    it('each weekday starts fresh with no carryover', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.2')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.2'))]));
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.no_lessons'));
        $bot->assertActiveConversation();
    });

    it('correct Russian weekday names are shown', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.2')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, strtolower(__('general.weekday.1')));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, strtolower(__('general.weekday.2')));
        $bot->assertActiveConversation();
    });

    it('after last marked weekday auto-completes', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('info.schedule.created'));
        $bot->assertNoConversation();
    });
});

describe('Schedule creation and completion', function () {
    it('database entries created for each lesson', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 2);
    });

    it('entries have correct class_id subject_id weekday and lesson_number', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseHas('weekly_schedule_entries', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'weekday' => 1,
            'lesson_number' => 1,
        ]);
    });

    it('no entries created for unmarked weekdays', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 1);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['weekday' => 2]);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['weekday' => 3]);
    });

    it('success message shown after completion', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('info.schedule.created'));
    });

    it('conversation ends after completion', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $bot->assertNoConversation();
    });
});

describe('Cancel behavior', function () {
    it('/cancel at weekday selection ends conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('/cancel at lesson selection ends conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('/cancel at confirmation ends conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('no database changes on cancel', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearText('/cancel')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 0);
    });
});

describe('Edge cases', function () {
    it('single subject class works', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 1);
        assertReplyContains($bot, __('info.schedule.created'));
    });

    it('many subjects work', function () {
        $class = Classroom::factory()->create();
        $subjects = Subject::factory()->count(10)->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        foreach ($subjects as $subject) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        }
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 10);
    });

    it('only Sunday selected works', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.7')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.creating_schedule', ['weekday' => strtolower(__('general.weekday.7'))]));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 1);
        $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => 7]);
    });

    it('alternating weekdays marked processes correctly', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.3')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.5')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.7')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        for ($i = 0; $i < 4; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseCount('weekly_schedule_entries', 4);
        $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => 1]);
        $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => 3]);
        $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => 5]);
        $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => 7]);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['weekday' => 2]);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['weekday' => 4]);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['weekday' => 6]);
    });

    it('single weekday with many lessons saves in order', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $subject3 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Chemistry']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.add.1')->reply();
        $bot->hearCallbackQueryData('newschedule.weekday.done.done')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject3->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $this->assertDatabaseHas('weekly_schedule_entries', [
            'weekday' => 1,
            'lesson_number' => 1,
            'subject_id' => $subject2->id,
        ]);
        $this->assertDatabaseHas('weekly_schedule_entries', [
            'weekday' => 1,
            'lesson_number' => 2,
            'subject_id' => $subject1->id,
        ]);
        $this->assertDatabaseHas('weekly_schedule_entries', [
            'weekday' => 1,
            'lesson_number' => 3,
            'subject_id' => $subject3->id,
        ]);
    });
});
