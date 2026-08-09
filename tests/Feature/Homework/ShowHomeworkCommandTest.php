<?php

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyScheduleEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;

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
            'text' => __('prompt.homework.select_period'),
        ]);
        assertReplyMarkupContains($bot, [
            __('button_labels.keyboard.tomorrow'),
            'showhomework.date.tomorrow',
            __('button_labels.keyboard.this_week'),
            'showhomework.date.this_week',
            __('button_labels.keyboard.next_week'),
            'showhomework.date.next_week',
            __('button_labels.keyboard.custom'),
            'showhomework.date.custom',
        ]);
    });

    it('returns error when user not in any class', function () {
        $user = User::factory()->student()->create(['class_id' => null]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        assertReplyContains($bot, __('error.homework.not_in_class'));
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
        assertReplyContains($bot, __('prompt.general.click_button'));
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
        assertReplyContains($bot, __('prompt.general.click_button'));
        $bot->assertActiveConversation();
    });

    it('processes "tomorrow" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->addDay()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'subject_id' => $subject->id,
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
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->addDay()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('processes "next week" callback', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->addDay()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.next_week')->reply();
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
        assertReplyContains($bot, __('prompt.homework.enter_date_format'));
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
        assertReplyContains($bot, __('prompt.homework.enter_date_format'));
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
        assertReplyContains($bot, __('prompt.general.enter_date_text'));
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
        assertReplyContains($bot, __('error.homework.date_empty'));
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
        assertReplyContains($bot, __('error.homework.date_invalid'));
        $bot->assertActiveConversation();
    });

    it('accepts YYYY-MM-DD format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'weekday' => now()->isoWeekday()]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText(now()->format('Y-m-d'))->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('accepts DD.MM.YYYY format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'weekday' => now()->isoWeekday()]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

    it('accepts DD.MM format', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'weekday' => now()->isoWeekday()]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m'))->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });

});

describe('ShowHomework homework display', function () {
    it('shows correct schedule structure', function () {
        $class = Classroom::factory()->create();
        $subjects = Subject::factory(2)->create(['class_id' => $class->id]);
        $scheduleEntries = collect([1 => 5, 2 => 6, 3 => 2])
            ->flatMap(
                fn (int $lessonsCount, int $weekday) => collect(range(1, $lessonsCount))->map(
                    fn (int $lesson_number) => [
                        'weekday' => $weekday,
                        'lesson_number' => $lesson_number,
                        'class_id' => $class->id,
                        'subject_id' => $subjects->random()->id,
                    ]
                )
            );
        $scheduleEntries = WeeklyScheduleEntry::factory()->createMany($scheduleEntries);
        $bot = bot(User::factory()->create(['class_id' => $class->id]));
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();
        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        collect([
            __('general.weekday.1'),
            __('general.weekday.2'),
            __('general.weekday.3'),
            __('info.homework.no_homework'),
            ...($subjects->pluck('name')->toArray()),
        ])->each(
            fn (string $ss) => assertReplyContains($bot, $ss)
        );
    });
    it('applies homework for correct date', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $scheduleEntries = collect([1 => 1, 2 => 1])
            ->flatMap(
                fn (int $lessonsCount, int $weekday) => collect(range(1, $lessonsCount))->map(
                    fn (int $lesson_number) => [
                        'weekday' => $weekday,
                        'lesson_number' => $lesson_number,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                    ]
                )
            );
        $scheduleEntries = WeeklyScheduleEntry::factory()->createMany($scheduleEntries);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'date' => now()->startOfWeek(Carbon::MONDAY),
        ]);
        $bot = bot(User::factory()->create(['class_id' => $class->id]));
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();
        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyContains(
            $bot,
            __('general.weekday.1'),
        );
        assertReplyContains(
            $bot,
            __('general.weekday.2'),
        );
        assertReplyContains(
            $bot,
            $subject->name.': '.$homework->description
        );
        assertReplyContains(
            $bot,
            $subject->name.': '.__('info.homework.no_homework')
        );

    });
    it('shows homeworks for this week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => 1, 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'Тестовое ДЗ на эту неделю',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows homeworks for next week', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => 1, 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'description' => 'Тестовое ДЗ на следующую неделю',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.next_week')->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows homeworks for custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Тестовое ДЗ на '.now()->format('d').' июня',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();
        assertReplyContains($bot, $homework->description);
    });

    it('shows no homeworks message when none exist', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $s = Subject::factory()->for($class)->create();
        WeeklyScheduleEntry::factory()->for($class)->create(['subject_id' => $s->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyContains($bot, __('info.homework.no_homework'));
        $bot->assertNoConversation();
    });

    it('does not show homeworks from other classes', function () {
        $class1 = Classroom::factory()->create();
        $class2 = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class1->id]);
        $subjectOtherClass = Subject::factory()->create(['class_id' => $class2->id]);
        $homeworkOtherClass = Homework::factory()->create([
            'class_id' => $class2->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'ДЗ из другого класса',
            'subject_id' => $subjectOtherClass->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyMarkupNotContains($bot, [$homeworkOtherClass->description]);
    });

    it('groups homeworks by date with day names', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyContains($bot, (new DateTime($homework->date))->format('d.m'));
    });

    it('formats header with date range for week selections', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory(6, new Sequence(
            ['weekday' => 1, 'lesson_number' => 1, 'subject_id' => $subject->id],
            ['weekday' => 2, 'lesson_number' => 1, 'subject_id' => $subject->id],
            ['weekday' => 3, 'lesson_number' => 1, 'subject_id' => $subject->id],
            ['weekday' => 4, 'lesson_number' => 1, 'subject_id' => $subject->id],
            ['weekday' => 5, 'lesson_number' => 1, 'subject_id' => $subject->id],
            ['weekday' => 6, 'lesson_number' => 1, 'subject_id' => $subject->id],
        ))->create(['class_id' => $class->id]);
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();
        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();

        assertReplyContains($bot, __('info.homework.view_header_range', ['start' => now()->startOfWeek()->format('d.m'), 'end' => now()->endOfWeek()->format('d.m')]));
    });

    it('formats header with single date for tomorrow', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory(1, ['class_id' => $class->id, 'subject_id' => $subject->id, 'weekday' => now()->addDay()->isoWeekday(), 'lesson_number' => 1])->create();
        Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.tomorrow')->reply();
        assertReplyContains($bot, __('info.homework.view_header', ['start' => now()->addDay()->format('d.m')]));
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
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->startOfWeek()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        $bot->assertNoConversation();
        assertReplyContains($bot, $homework->description);
    });
});

describe('ShowHomework full conversation flow', function () {
    it('complete flow with tomorrow selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->startOfWeek()->isoWeekday(), 'subject_id' => $subject->id]);
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'description' => 'Завтрашнее ДЗ',
            'subject_id' => $subject->id,
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
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory(1, ['weekday' => 1])->for($class)->for($subject)->create();
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->startOfWeek()->toDateString(),
            'description' => 'ДЗ на эту неделю',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();
        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with next week selection', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create(['class_id' => $class->id]);
        WeeklyScheduleEntry::factory(1, ['weekday' => 1])->for($class)->for($subject)->create();
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'description' => 'ДЗ на следующую неделю',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.next_week')->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with custom date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->create([
            'class_id' => $class->id,
        ]);
        WeeklyScheduleEntry::factory(1, ['weekday' => now()->isoWeekday()])->for($class)->for($subject)->create();
        $homework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'ДЗ на '.now()->format('d').' июня',
            'subject_id' => $subject->id,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.custom')->reply();
        $bot->hearText(now()->format('d.m.Y'))->reply();

        assertReplyContains($bot, $homework->description);
        $bot->assertNoConversation();
    });

    it('complete flow with no homeworks in range', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $subject = Subject::factory()->for($class)->create();
        WeeklyScheduleEntry::factory()->create(['class_id' => $class->id, 'weekday' => now()->startOfWeek()->isoWeekday(), 'subject_id' => $subject->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/showhomework')
            ->reply();

        $bot->assertActiveConversation();

        $bot->hearCallbackQueryData('showhomework.date.this_week')->reply();

        assertReplyContains($bot, __('info.homework.no_homework'));
        $bot->assertNoConversation();
    });
});
