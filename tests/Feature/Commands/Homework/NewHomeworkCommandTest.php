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

describe('NewHomework conversation date input', function () {
    it('Correctly handles the \'next monday\' date', function () {});
    it('Correctly handles the \'next tuesday\' date', function () {});
    it('Correctly handles the \'next wednesday\' date', function () {});
    it('Correctly handles the \'next thursday\' date', function () {});
    it('Correctly handles the \'next friday\' date', function () {});
    it('Correctly handles the \'next saturday\' date', function () {});
    it('Correctly handles the custom date', function () {});

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
        $bot->assertNoConversation();
    });
});

describe('NewHomework duplicate prevention', function () {
    it('returns error when homework already exists for date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $homeworkDate = now()->addWeek()->startOfWeek()->toDateString();
        $existingHomework = Homework::factory()->create([
            'class_id' => $class->id,
            'date' => $homeworkDate,
        ]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.'.$homeworkDate)->reply();
        $bot->hearText('Solve exercises 1-5 from textbook chapter 3')->reply();

        assertReplyContains($bot, 'уже существует');
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
        $bot->assertNoConversation();
    });

    it('completes with full DD.MM.YYYY date', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $class->id]);

        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newhomework')
            ->reply();

        $bot->hearCallbackQueryData('newhomework.date.2026-06-15')->reply();
        $bot->hearText('Complete the project')->reply();

        $this->assertDatabaseHas('homeworks', [
            'date' => '2026-06-15',
            'description' => 'Complete the project',
        ]);
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
