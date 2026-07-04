<?php

use App\Models\Classroom;
use App\Models\User;

describe('NewClass command', function () {
    it('starts conversation and prompts for class name', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();
        assertReplyContains($bot, __('prompt.class.enter_name'));
        $bot->assertActiveConversation();
    });

    it('returns error when user already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $bot = botWithData(['code' => 'fake'], $user->id, $user->first_name);
        $bot->hearText('/newclass fake')->reply();
        assertReplyContains($bot, __('error.class.already_member'));
    });

    it('returns error when user already in a class and starts conversation', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();
        assertReplyContains($bot, __('error.class.already_member'));
    });
});

describe('NewClass conversation validation', function () {
    it('rejects empty input', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('')->reply();
        assertReplyContains($bot, __('prompt.class.name_invalid'));
        $bot->assertActiveConversation();
    });

    it('rejects input without Russian letter', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('10')->reply();
        $bot->assertActiveConversation();
    });

    it('rejects input starting with letter', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('А1')->reply();
        $bot->assertActiveConversation();
    });

    it('rejects input with too many digits', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('123Б')->reply();
        $bot->assertActiveConversation();
    });

    it('rejects invalid digit after 10', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('12А')->reply();
        $bot->assertActiveConversation();
    });

    it('rejects input that is too long', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('101АБ')->reply();
        $bot->assertActiveConversation();
    });
});

describe('NewClass conversation valid inputs', function () {
    it('creates class with single digit and letter', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('5Г')->reply();
        assertReplyContains($bot, '5Г');
        $this->assertDatabaseHas('classes', ['code' => '5Г']);
        $class = Classroom::where('code', '5Г')->first();
        $this->assertNotNull($class);
        assertReplyContains($bot, $class->join_token);
        $bot->assertNoConversation();
    });

    it('creates class with two digits and letter', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('10Б')->reply();
        assertReplyContains($bot, '10Б');
        $this->assertDatabaseHas('classes', ['code' => '10Б']);
    });

    it('creates class with lowercase letter', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();

        $bot->hearText('11в')->reply();
        assertReplyContains($bot, '11В');
        $bot->assertNoConversation();
    });
});

describe('NewClass full conversation flow', function () {
    it('completes class creation and cancel works afterwards', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);

        $bot->willStartConversation(remember: true)
            ->hearText('/newclass')
            ->reply();
        $bot->assertActiveConversation();

        $bot->hearText('10Б')->reply();
        assertReplyContains($bot, '10Б');
        $user->refresh();
        $this->assertNotNull($user->class_id);
        $bot->assertNoConversation();

        $bot->hearText('/cancel')->reply();
        assertReplyContains($bot, __('error.cancel.no_active'));
    });
});
