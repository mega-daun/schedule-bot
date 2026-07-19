<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('NewSchedule command', function () {
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
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
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
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
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
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
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

describe('NewSchedule subject selection', function () {
    it('shows Monday menu with correct text', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
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
        assertReplyMarkupContains($bot, ['Mathematics', 'Physics', 'Chemistry', 'Готово']);
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

    it('selecting subject adds marker via editMessage', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->assertReplyMessage(['text' => __('prompt.schedule.select_subjects', ['weekday' => 'понедельник'])], index: 0);
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)']);
        $bot->assertActiveConversation();
    });

    it('selecting second subject adds marker', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)', 'Physics✅ (2)']);
        $bot->assertActiveConversation();
    });

    it('unmarking subject removes marker', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics']);
        assertReplyMarkupNotContains($bot, ['Mathematics✅ (1)']);
        $bot->assertActiveConversation();
    });

    it('unmarking decrements subsequent numbers', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $subject3 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Chemistry']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject3->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)', 'Chemistry✅ (2)', 'Physics']);
        $bot->assertActiveConversation();
    });

    it('subject stays in menu when unmarked', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics']);
        $bot->assertActiveConversation();
    });

    it('can select all subjects', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)', 'Physics✅ (2)']);
        $bot->assertActiveConversation();
    });

    it('can deselect all subjects', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics', 'Physics']);
        assertReplyMarkupNotContains($bot, ['Mathematics✅ (1)', 'Physics✅ (1)']);
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

    it('non-callback at selection step shows error', function () {
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

describe('NewSchedule confirmation flow', function () {
    it('clicking Готово shows preview', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.schedule_preview', ['weekday' => 'понедельник']));
        $bot->assertActiveConversation();
    });

    it('preview format is correct', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $subject3 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Chemistry']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject3->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, '1. Mathematics\\n2. Physics\\n3. Chemistry');
        $bot->assertActiveConversation();
    });

    it('preview shows Нет уроков for empty', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyContains($bot, __('prompt.schedule.no_lessons'));
        $bot->assertActiveConversation();
    });

    it('confirming moves to Tuesday', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'вторник']));
        $bot->assertActiveConversation();
    });

    it('not confirming returns to menu', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.no')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)']);
        $bot->assertActiveConversation();
    });

    it('confirmation buttons are Да and Нет', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        assertReplyMarkupContains($bot, ['Да', 'Нет']);
        $bot->assertActiveConversation();
    });
});

describe('NewSchedule weekday progression', function () {
    it('Monday to Tuesday progression', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'вторник']));
        $bot->assertActiveConversation();
    });

    it('Tuesday to Wednesday progression', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'среда']));
        $bot->assertActiveConversation();
    });

    it('Wednesday to Thursday progression', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'четверг']));
        $bot->assertActiveConversation();
    });

    it('Thursday to Friday progression', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 4; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'пятница']));
        $bot->assertActiveConversation();
    });

    it('Friday to Saturday progression', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 5; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'суббота']));
        $bot->assertActiveConversation();
    });

    it('each day shows correct Russian name', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'понедельник']));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'вторник']));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'среда']));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'четверг']));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'пятница']));
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyContains($bot, __('prompt.schedule.select_subjects', ['weekday' => 'суббота']));
    });

    it('each day starts fresh', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        assertReplyMarkupNotContains($bot, ['Mathematics✅ (1)']);
        $bot->assertActiveConversation();
    });
});

describe('NewSchedule completion', function () {
    it('Saturday confirmation creates all entries', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseCount('weekly_schedule_entries', 6);
    });

    it('entries have correct class_id', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseHas('weekly_schedule_entries', ['class_id' => $class->id]);
    });

    it('entries have correct subject_id', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseHas('weekly_schedule_entries', ['subject_id' => $subject->id]);
    });

    it('entries have correct weekday', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        for ($i = 1; $i <= 6; $i++) {
            $this->assertDatabaseHas('weekly_schedule_entries', ['weekday' => $i]);
        }
    });

    it('entries have correct lesson_number', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseHas('weekly_schedule_entries', ['lesson_number' => 1]);
    });

    it('success message shown', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        assertReplyContains($bot, __('info.schedule.created'));
        $bot->assertNoConversation();
    });

    it('conversation ends after completion', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $bot->assertNoConversation();
    });
});

describe('NewSchedule cancel behavior', function () {
    it('/cancel at Monday menu ends conversation', function () {
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

    it('/cancel at confirmation step ends conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('/cancel resets entire command progress', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
        $this->assertDatabaseCount('weekly_schedule_entries', 0);
    });

    it('no database changes on cancel', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.done')->reply();
        $bot->hearText('/cancel')->reply();
        $this->assertDatabaseCount('weekly_schedule_entries', 0);
    });
});

describe('NewSchedule edge cases', function () {
    it('single subject class works', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        for ($i = 0; $i < 6; $i++) {
            $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
            $bot->hearCallbackQueryData('newschedule.select.done')->reply();
            $bot->hearCallbackQueryData('newschedule.confirm.yes')->reply();
        }
        $this->assertDatabaseCount('weekly_schedule_entries', 6);
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
        $bot->assertActiveConversation();
    });

    it('selecting subjects in different orders', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Physics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject2->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject1->id)->reply();
        assertReplyMarkupContains($bot, ['Physics✅ (1)', 'Mathematics✅ (2)']);
        $bot->assertActiveConversation();
    });

    it('rapid selection/deselection', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newschedule')
            ->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        $bot->hearCallbackQueryData('newschedule.select.'.$subject->id)->reply();
        assertReplyMarkupContains($bot, ['Mathematics✅ (1)']);
        $bot->assertActiveConversation();
    });
});
