<?php

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;

describe('JoinClassCommand (with token)', function () {
    it('successfully joins class with valid token', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);
        $bot->hearText('/joinclass '.$classroom->join_token)->reply();
        assertReplyContains($bot, $classroom->code);
        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
    });

    it('resets user role to Student after joining', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => null]);
        $this->assertEquals(UserRole::Admin, $user->role);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);
        $bot->hearText('/joinclass '.$classroom->join_token)->reply();
        $user->refresh();
        $this->assertEquals(UserRole::Student, $user->role);
    });

    it('preserves user data when joining', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create([
            'first_name' => 'Иван',
            'username' => 'ivan_test',
            'class_id' => null,
        ]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);
        $bot->hearText('/joinclass '.$classroom->join_token)->reply();
        $user->refresh();
        $this->assertEquals('Иван', $user->first_name);
        $this->assertEquals('ivan_test', $user->username);
    });

    it('returns error when token does not match any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = botWithData(['token' => 'nonexistent_token_1234'], $user->id, $user->first_name);
        $bot->hearText('/joinclass nonexistent_token_1234')->reply();
        assertReplyContains($bot, 'Класс не найден');
        $user->refresh();
        $this->assertNull($user->class_id);
    });

    it('returns error when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $bot = botWithData(['token' => $newClass->join_token], $user->id, $user->first_name);
        $bot->hearText('/joinclass '.$newClass->join_token)->reply();
        assertReplyContains($bot, 'Вы уже состоите в классе');
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
        $bot->assertNoConversation($user->id, $user->id);
    });

    it('sets user class_id correctly', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);
        $bot->hearText('/joinclass '.$classroom->join_token)->reply();
        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'class_id' => $classroom->id,
        ]);
    });
});

describe('JoinClassCommand (no token)', function () {
    it('starts conversation when no token provided', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();
        assertReplyContains($bot, 'токен');
        $bot->assertActiveConversation();
    });

    it('prompts user to enter token', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();
        $bot->assertActiveConversation();
    });

    it('already in class returns error instead of starting conversation', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $classroom->id]);
        $bot = botWithData(['token' => 'fake_token'], $user->id, $user->first_name);
        $bot->hearText('/joinclass fake_token')->reply();
        assertReplyContains($bot, 'Вы уже состоите в классе');
        $bot->assertNoConversation($user->id, $user->id);
    });
});

describe('JoinClass conversation validation', function () {
    it('rejects empty input', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();

        $bot->hearText('')->reply();
        assertReplyContains($bot, 'токен');
        $bot->assertActiveConversation();
    });

    it('rejects token that does not match any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();

        // Use 16-char valid format token that doesn't exist
        $bot->hearText('abcdef1234567890')->reply();
        assertReplyContains($bot, 'Класс с таким токеном не найден');
        $bot->assertActiveConversation();
    });

    it('accepts valid token and joins class', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();

        $bot->hearText($classroom->join_token)->reply();
        assertReplyContains($bot, $classroom->code);
        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        $bot->assertNoConversation();
    });

    it('resets role to Student after joining via conversation', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => null]);
        $this->assertEquals(UserRole::Admin, $user->role);
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();

        $bot->hearText($classroom->join_token)->reply();
        $user->refresh();
        $this->assertEquals(UserRole::Student, $user->role);
        $bot->assertNoConversation();
    });

    it('rejects input when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $bot = bot($user);
        $bot->willStartConversation(remember: true)
            ->hearText('/joinclass')
            ->reply();

        $bot->hearText($newClass->join_token)->reply();
        assertReplyContains($bot, 'Вы уже состоите в классе');
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
    });
});
