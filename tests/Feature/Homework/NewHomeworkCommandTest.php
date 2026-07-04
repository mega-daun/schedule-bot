<?php

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\User;

describe('NewHomework command', function () {
    it('prompts for date selection when user in a class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => 'Выберите дату',
        ]);
    });

    it('returns error when user not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();
        assertReplyContains($bot, 'Вы не состоите в классе');
    });

    it('returns error when user already in a conversation', function () {
        $class = Classroom::factory()->create();
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
});

describe('NewHomework date selection', function () {
    it('rejects non-callback query at date selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearText('some text')->reply();
        assertReplyContains($bot, 'Нажмите на кнопку');
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data and prompts again', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, 'Нажмите на кнопку');
        $bot->assertActiveConversation();
    });

    it('processes valid callback data and prompts for description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        assertReplyContains($bot, 'Введите описание домашнего задания');
        $bot->assertActiveConversation();
    });
});

describe('NewHomework custom date input', function () {
    it('prompts for text date when custom option selected', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        assertReplyContains($bot, 'Введите дату в формате');
        $bot->assertActiveConversation();
    });

    it('rejects callback query at date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearCallbackQueryData('some.callback')->reply();
        assertReplyContains($bot, 'Введите дату текстом');
        $bot->assertActiveConversation();
    });

    it('rejects empty date input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, 'Дата не может быть пустой');
        $bot->assertActiveConversation();
    });

    it('rejects invalid date format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('not a date')->reply();
        assertReplyContains($bot, 'Неверный формат даты');
        $bot->assertActiveConversation();
    });

    it('accepts YYYY-MM-DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('Y-m-d'))->reply();
        assertReplyContains($bot, 'Введите описание домашнего задания');
        $bot->assertActiveConversation();
    });

    it('accepts DD.MM.YYYY format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        assertReplyContains($bot, 'Введите описание домашнего задания');
        $bot->assertActiveConversation();
    });

    it('accepts DD.MM format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m'))->reply();
        assertReplyContains($bot, 'Введите описание домашнего задания');
        $bot->assertActiveConversation();
    });

    it('accepts DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.custom')->reply();
        $bot->hearText('15')->reply();
        assertReplyContains($bot, 'Введите описание домашнего задания');
        $bot->assertActiveConversation();
    });
});

describe('NewHomework conversation description input', function () {
    it('rejects empty description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, 'описание');
    });

    it('rejects too short description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearText('ab')->reply();
        assertReplyContains($bot, 'описание');
    });

    it('creates homework with valid description', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearText('Read chapter 5 carefully and write a summary')->reply();

        $this->assertDatabaseHas('homeworks', [
            'class_id' => $class->id,
            'description' => 'Read chapter 5 carefully and write a summary',
        ]);
        assertReplyContains($bot, 'успешно создано');
        $bot->assertNoConversation();
    });
});

describe('NewHomework full conversation flow', function () {
    it('completes full conversation and creates homework with DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('newhomework.date.'.now()->addWeek()->startOfWeek()->format('Y-m-d'))->reply();
        $bot->hearText('Solve exercises 1-5 from textbook chapter 3')->reply();

        $user->refresh();
        $this->assertNotNull($user->class_id);
        assertReplyContains($bot, 'успешно создано');
        $bot->assertNoConversation();
    });

    it('completes with full DD.MM.YYYY date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.' . now()->format('Y-m-d'))->reply();
        $bot->hearText('Complete the project')->reply();

        $this->assertDatabaseHas('homeworks', [
            'date' => now()->format('Y-m-d'),
            'description' => 'Complete the project',
        ]);
        assertReplyContains($bot, 'успешно создано');
    });

    it('cancel works at any step', function () {
        $class = Classroom::factory()->create();
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
