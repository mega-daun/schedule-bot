<?php

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;
use App\Telegram\Conversations\Homework\NewHomeworkConversation;

describe('NewHomework command', function () {
    it('prompts for date selection when user in a class', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => __('prompt.homework.select_date'),
        ]);
    });

    it('returns error when user not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();
        assertReplyContains($bot, __('error.homework.not_in_class'));
    });

    it('returns error when user already in a conversation', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();
    });

    it('returns error when there is no subjects in class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();
        
        assertReplyContains($bot, __('error.class.no_subjects'));
    });
});

describe('NewHomework date selection', function () {
    it('rejects non-callback query at date selection step', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data and prompts again', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('processes valid callback data and prompts for subject', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        $bot->assertActiveConversation();
    });
});

describe('NewHomework custom date input', function () {
    it('prompts for text date when custom option selected', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        assertReplyContains($bot, __('prompt.homework.enter_date_format'));
        $bot->assertActiveConversation();
    });

    it('rejects callback query at date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearCallbackQueryData('some.callback')->reply();
        assertReplyContains($bot, __('prompt.general.enter_date_text'));
        $bot->assertActiveConversation();
    });

    it('rejects empty date input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, __('error.homework.date_empty'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid date format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('not a date')->reply();
        assertReplyContains($bot, __('error.homework.date_invalid'));
        $bot->assertActiveConversation();
    });

    it('accepts YYYY-MM-DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('Y-m-d'))->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        $bot->assertActiveConversation();
    });

    it('accepts DD.MM.YYYY format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        $bot->assertActiveConversation();
    });

    it('accepts DD.MM format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m'))->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        $bot->assertActiveConversation();
    });

    it('accepts DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('15')->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        $bot->assertActiveConversation();
    });
});

describe('NewHomework conversation subject input', function () {
    it('rejects non-callback query at selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearText('some text')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects subject for another class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $otherClass = Classroom::factory()->create();
        $otherSubject = Subject::factory()->create(['class_id' => $otherClass->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$otherSubject->id)->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback query at selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('prompts for subject using menu', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $s = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        assertReplyContains($bot, __('prompt.homework.select_subject'));
        assertReplyMarkupContains($bot, [$s->name, (string)$s->id]);        
        $bot->assertActiveConversation();
    });

    it('correctly handles subject selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        assertReplyContains($bot, __('prompt.homework.enter_description'));
        $bot->assertActiveConversation();
    });
});

describe('NewHomework conversation description input', function () {
    it('rejects empty description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, __('error.homework.description_empty'));
    });

    it('rejects too short description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        $bot->hearText('ab')->reply();
        assertReplyContains($bot, __('error.homework.description_too_short', ['min' => 12]));
    });

    it('creates homework with valid description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        $bot->hearText('Read chapter 5 carefully and write a summary')->reply();

        $this->assertDatabaseHas('homeworks', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'description' => 'Read chapter 5 carefully and write a summary',
        ]);
        assertReplyContains($bot, __('info.homework.created'));
        $bot->assertNoConversation();
    });
});

describe('NewHomework full conversation flow', function () {
    it('completes full conversation and creates homework with DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        $bot->hearText('Solve exercises 1-5 from textbook chapter 3')->reply();

        $user->refresh();
        $this->assertNotNull($user->class_id);
        assertReplyContains($bot, __('info.homework.created'));
        $bot->assertNoConversation();
    });

    it('completes with full DD.MM.YYYY date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.' . now()->format('Y-m-d'))->reply();
        $bot->hearCallbackQueryData('newhomework.subject.'.$subject->id)->reply();
        $bot->hearText('Complete the project')->reply();

        $this->assertDatabaseHas('homeworks', [
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
            'description' => 'Complete the project',
        ]);
        assertReplyContains($bot, __('info.homework.created'));
    });

    it('cancel works at any step', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id]);
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });
});
