<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('NewSubject command', function () {
    it('starts conversation and prompts for subject name', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.enter_name'));
        $bot->assertActiveConversation();
    });

    it('returns error when user is not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, __('error.class.not_member'));
    });

    it('returns error when user has Student role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
    });

    it('allows Teacher role to start conversation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->teacher()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.enter_name'));
        $bot->assertActiveConversation();
    });

    it('allows OnDuty role to start conversation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->onDuty()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, __('prompt.subject.enter_name'));
        $bot->assertActiveConversation();
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
            ->hearText('/newsubject')
            ->reply();

        $bot->assertActiveConversation();
    });
});

describe('NewSubject conversation validation', function () {
    it('rejects empty input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('')->reply();
        assertReplyContains($bot, __('error.subject.name_empty'));
        $bot->assertActiveConversation();
    });

    it('rejects input shorter than 3 symbols', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Ma')->reply();
        assertReplyContains($bot, __('error.subject.name_too_short', ['min' => 3]));
        $bot->assertActiveConversation();
    });

    it('rejects input with exactly 2 symbols', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('AB')->reply();
        assertReplyContains($bot, __('error.subject.name_too_short', ['min' => 3]));
        $bot->assertActiveConversation();
    });

    it('accepts input with exactly 3 symbols', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Math')->reply();
        assertReplyContains($bot, __('info.subject.created', ['name' => 'Math']));
        $this->assertDatabaseHas('subjects', ['class_id' => $class->id, 'name' => 'Math']);
        $bot->assertNoConversation();
    });

    it('rejects duplicate subject name in same class', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Mathematics')->reply();
        assertReplyContains($bot, __('error.subject.already_exists'));
        $bot->assertActiveConversation();
    });

    it('allows same subject name in different class', function () {
        $class1 = Classroom::factory()->create();
        $class2 = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class1->id, 'name' => 'Mathematics']);
        $user = User::factory()->admin()->create(['class_id' => $class2->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Mathematics')->reply();
        assertReplyContains($bot, __('info.subject.created', ['name' => 'Mathematics']));
        $this->assertDatabaseHas('subjects', ['class_id' => $class2->id, 'name' => 'Mathematics']);
        $bot->assertNoConversation();
    });

    it('creates subject in database with correct class_id', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Physics')->reply();
        $this->assertDatabaseHas('subjects', [
            'class_id' => $class->id,
            'name' => 'Physics',
        ]);
    });

    it('cancel works', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->assertActiveConversation();
        $bot->hearText('/cancel')->reply();
        $bot->assertNoConversation();
    });
});

describe('NewSubject full conversation flow', function () {
    it('completes full flow with valid input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);

        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearText('Mathematics')->reply();
        assertReplyContains($bot, __('info.subject.created', ['name' => 'Mathematics']));
        $this->assertDatabaseHas('subjects', ['class_id' => $class->id, 'name' => 'Mathematics']);
        $bot->assertNoConversation();
    });

    it('retry after invalid input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => $class->id]);
        $bot = bot($user);

        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearText('')->reply();
        assertReplyContains($bot, __('error.subject.name_empty'));
        $bot->assertActiveConversation();

        $bot->hearText('Ma')->reply();
        assertReplyContains($bot, __('error.subject.name_too_short', ['min' => 3]));
        $bot->assertActiveConversation();

        $bot->hearText('Mathematics')->reply();
        assertReplyContains($bot, __('info.subject.created', ['name' => 'Mathematics']));
        $bot->assertNoConversation();
    });
});
