<?php

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\User;

describe('ShowHomework command', function () {
    it('prompts for date selection when user in a class', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();
        $bot->assertReply('sendMessage', [
            'text' => 'Выберите период',
        ]);
        assertReplyMarkupContains($bot, [
            'Завтра',
            'showhomework.date.tomorrow',
            'Эта неделя',
            'showhomework.date.thisweek',
            'Следующая неделя',
            'showhomework.date.nextweek',
            'Свой вариант',
            'showhomework.date.custom',
        ]);
    });

    it('returns error when user not in any class', function () {
        $user = User::factory()->student()->create(['class_id' => null]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        assertReplyContains($bot, 'Вы не состоите в классе');
    });

    it('returns error when user already in a conversation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();
    });
});

describe('ShowHomework date selection', function () {
    it('rejects non-callback query at date selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearText('some text')->reply();
        assertReplyContains($bot, 'Нажмите на кнопку');
        $bot->assertActiveConversation();
    });

    it('rejects invalid callback data and prompts again', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('invalid.data.here')->reply();
        assertReplyContains($bot, 'Нажмите на кнопку');
        $bot->assertActiveConversation();
    });

    it('processes "tomorrow" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.tomorrow')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('processes "this week" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('processes "next week" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.nextweek')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('processes "custom" callback and prompts for date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        assertReplyContains($bot, 'Введите дату в формате');
        $bot->assertActiveConversation();
    });
});

describe('ShowHomework custom date input', function () {
    it('prompts for text date when custom option selected', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        assertReplyContains($bot, 'Введите дату в формате');
        $bot->assertActiveConversation();
    });

    it('rejects callback query at date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearCallbackQueryData('some.callback')->reply();
        assertReplyContains($bot, 'Введите дату текстом');
        $bot->assertActiveConversation();
    });

    it('rejects empty date input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('')->reply();
        assertReplyContains($bot, 'Дата не может быть пустой');
        $bot->assertActiveConversation();
    });

    it('rejects invalid date format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('not a date')->reply();
        assertReplyContains($bot, 'Неверный формат даты');
        $bot->assertActiveConversation();
    });

    it('accepts YYYY-MM-DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('2026-06-15')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('accepts DD.MM.YYYY format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15.06.2026')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('accepts DD.MM format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15.06')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('accepts DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });
});

describe('ShowHomework homework display', function () {
    it('shows homeworks for this week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'Тестовое ДЗ на эту неделю',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows homeworks for next week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'description' => 'Тестовое ДЗ на следующую неделю',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.nextweek')->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows homeworks for custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
            'description' => 'Тестовое ДЗ на 15 июня',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15.06.2026')->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows no homeworks message when none exist', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, 'Нет домашних заданий за выбранный период');
        $bot->assertNoConversation();
    });

    it('does not show homeworks from other classes', function () {
        $class1 = Classroom::factory()->create();
        $class2 = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class1->id]);
        $homeworkOtherClass = Homework::factory()->create([
            'class_id' => $class2->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'ДЗ из другого класса',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyMarkupNotContains($bot, [$homeworkOtherClass->description]);
    });

    it('groups homeworks by date with day names', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, (new DateTime($homework->date))->format('d.m'));
    });

    it('formats header with date range for week selections', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, '❗️ДЗ на ' . now()->startOfWeek()->format('d.m') . '-' . now()->endOfWeek()->subDay()->format('d.m'));
    });

    it('formats header with single date for tomorrow', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.tomorrow')->reply();
        assertReplyContains($bot, '❗️ДЗ на ' . now()->addDay()->format('d.m'));
    });

    it('separates days with horizontal rule', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $monday = now()->startOfWeek()->toDateString();
        $tuesday = now()->startOfWeek()->addDay()->toDateString();
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $monday,
            'description' => 'ДЗ на понедельник',
        ]);
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $tuesday,
            'description' => 'ДЗ на вторник',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, 'Понедельник');
        assertReplyContains($bot, 'Вторник');
    });
});

describe('ShowHomework markdown format', function () {
    it('formats single day with description correctly', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
            'description' => 'Решить задачи по алгебре',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15.06.2026')->reply();

        assertReplyContains($bot, '❗️ДЗ на');
        assertReplyContains($bot, '15.06');
        assertReplyContains($bot, $homework->description);
    });

    it('formats multiple days correctly', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $monday = now()->startOfWeek()->toDateString();
        $tuesday = now()->startOfWeek()->addDay()->toDateString();
        $homeworkMonday = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $monday,
            'description' => 'ДЗ на понедельник',
        ]);
        $homeworkTuesday = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $tuesday,
            'description' => 'ДЗ на вторник',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();

        assertReplyContains($bot, $homeworkMonday->description);
        assertReplyContains($bot, $homeworkTuesday->description);
    });

    it('formats multiple homework items per day', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $date = now()->startOfWeek()->toDateString();
        $homework1 = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $date,
            'description' => 'Первое домашнее задание на сегодня',
        ]);
        $homework2 = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $date,
            'description' => 'Второе домашнее задание на сегодня',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();

        assertReplyContains($bot, $homework1->description);
        assertReplyContains($bot, $homework2->description);
    });

    it('displays full description text without truncation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $longDescription = str_repeat('Длинное описание задания ', 5);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => $longDescription,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        assertReplyContains($bot, $longDescription);
    });
});

describe('ShowHomework cancel and edge cases', function () {
    it('cancel works at date selection step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('cancel works at custom date input step', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });

    it('conversation ends after display', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();
        $bot->assertNoConversation();
    });
});

describe('ShowHomework full conversation flow', function () {
    it('complete flow with tomorrow selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'description' => 'Завтрашнее ДЗ',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.tomorrow')->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with this week selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'ДЗ на эту неделю',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with next week selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'description' => 'ДЗ на следующую неделю',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.nextweek')->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-06-15',
            'description' => 'ДЗ на 15 июня',
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText('15.06.2026')->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with no homeworks in range', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.thisweek')->reply();

        assertReplyContains($bot, 'Нет домашних заданий за выбранный период');
        $bot->assertNoConversation();
    });
});
