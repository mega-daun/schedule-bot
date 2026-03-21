<?php

use App\BotCommands\StartCommand;
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
        $response = runCommand($newUser, '/start', [StartCommand::class]);
        $this->assertArrayHasKey('text', $response, 'Bot sends some message');
        $this->assertStringContainsString($newUser['first_name'], $response['text'], 'Message is correct');
        $this->assertDatabaseHas('users', $newUser);
    });

    it('greets known user without arguments', function () {
        $user = User::factory()->create();
        $response = runCommandAs($user, '/start', [StartCommand::class]);
        $this->assertArrayHasKey('text', $response, 'Bot sends some message');
        $this->assertStringContainsString($user->first_name, $response['text'], 'Message is correct');
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
        $response = runCommand($newUser, '/start '.$classroom->join_token, [StartCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString($classroom->code, $response['text']);
        $this->assertDatabaseHas('users', [
            'id' => $newUser['id'],
            'class_id' => $classroom->id,
        ]);
    });

    it('returns error when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $response = runCommandAs($user, '/start '.$newClass->join_token, [StartCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('уже состоите в классе', $response['text']);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'class_id' => $existingClass->id,
        ]); // Class does not change
    });

    it('returns error when class not found', function () {
        $newUser = [
            'id' => 123456791,
            'first_name' => 'InvalidToken',
            'username' => 'invalid_token',
            'language_code' => 'en',
            'is_bot' => false,
        ];
        $response = runCommand($newUser, '/start invalid_token_123', [StartCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Класс не найден', $response['text']);
    });
});
