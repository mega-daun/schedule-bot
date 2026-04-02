<?php

use App\Models\Classroom;
use App\Models\User;

describe('Start command', function () {
    it('greets and registers new user without arguments', function () {
        $newUser = [
            'id' => 123456789,
            'first_name' => 'Yo',
            'username' => 'SomeUsername',
            'language_code' => 'en',
            'is_bot' => false,
        ];
        $bot = botWith($newUser['id'], $newUser['first_name'], $newUser['username'], $newUser['language_code']);
        $bot->hearText('/start')->reply();
        assertReplyContains($bot, 'Yo');
        $this->assertDatabaseHas('users', $newUser);
    });

    it('greets known user without arguments', function () {
        $user = User::factory()->create();
        $bot = bot($user);
        $bot->hearText('/start')->reply();
        assertReplyContains($bot, $user->first_name);
    });

    it('joins user to class with valid token', function () {
        $classroom = Classroom::factory()->create();
        $newUser = [
            'id' => 123456790,
            'first_name' => 'JoinTest',
            'username' => 'join_test',
            'language_code' => 'en',
            'is_bot' => false,
        ];
        $bot = botWithData(['token' => $classroom->join_token], $newUser['id'], $newUser['first_name']);
        $bot->hearText('/start')->reply();
        assertReplyContains($bot, $classroom->code);
        $this->assertDatabaseHas('users', [
            'id' => $newUser['id'],
            'class_id' => $classroom->id,
        ]);
    });

    it('returns error when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $bot = botWithData(['token' => $newClass->join_token], $user->id, $user->first_name);
        $bot->hearText('/start')->reply();
        assertReplyContains($bot, 'уже состоите в классе');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'class_id' => $existingClass->id,
        ]);
    });

    it('returns error when class not found', function () {
        $newUser = [
            'id' => 123456791,
            'first_name' => 'InvalidToken',
            'username' => 'invalid_token',
            'language_code' => 'en',
            'is_bot' => false,
        ];
        $bot = botWithData(['token' => 'invalid_token_123'], $newUser['id'], $newUser['first_name']);
        $bot->hearText('/start')->reply();
        assertReplyContains($bot, 'Класс не найден');
    });
});
