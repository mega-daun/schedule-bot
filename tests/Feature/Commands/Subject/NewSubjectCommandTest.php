<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('NewSubject command', function () {
    it('returns error when user has no class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->hearText('/newsubject')->reply();
        assertReplyContains($bot, 'Вы не состоите в классе');
    });

    it('returns error when user role is Student', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'ученик',
        ]);
        $bot = bot($user);
        $bot->hearText('/newsubject')->reply();
        assertReplyContains($bot, 'Вы не имеете право это сделать');
    });

    it('starts conversation for OnDuty role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'дежурный',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, 'название');
        $bot->assertActiveConversation();
    });

    it('starts conversation for Teacher role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'учитель',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, 'название');
        $bot->assertActiveConversation();
    });

    it('starts conversation for Admin role', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();
        assertReplyContains($bot, 'название');
        $bot->assertActiveConversation();
    });
});

describe('NewSubject conversation validation', function () {
    it('rejects empty input', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('')->reply();
        assertReplyContains($bot, 'название');
        $bot->assertActiveConversation();
    });

    it('rejects input that is too long', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $longInput = str_repeat('Т', 51);
        $bot->hearText($longInput)->reply();
        assertReplyContains($bot, 'название');
        $bot->assertActiveConversation();
    });

    it('rejects duplicate subject name in same class', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create([
            'class_id' => $class->id,
            'name' => 'Математика',
        ]);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Математика')->reply();
        assertReplyContains($bot, 'уже существует');
        $bot->assertActiveConversation();
    });
});

describe('NewSubject conversation valid inputs', function () {
    it('creates subject with valid name', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Математика')->reply();
        assertReplyContains($bot, 'Математика');
        $this->assertDatabaseHas('subjects', [
            'class_id' => $class->id,
            'name' => 'Математика',
        ]);
        $bot->assertNoConversation();
    });

    it('creates subject with name at max length', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $name = str_repeat('Т', 50);
        $bot->hearText($name)->reply();
        assertReplyContains($bot, $name);
        $this->assertDatabaseHas('subjects', [
            'class_id' => $class->id,
            'name' => $name,
        ]);
        $bot->assertNoConversation();
    });
});

describe('NewSubject conversation cancel', function () {
    it('can end conversation after subject creation', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/newsubject')
            ->reply();

        $bot->hearText('Математика')->reply();
        $bot->assertNoConversation();

        $bot->hearText('/cancel')->reply();
        assertReplyContains($bot, 'Нет активных действий');
    });
});
