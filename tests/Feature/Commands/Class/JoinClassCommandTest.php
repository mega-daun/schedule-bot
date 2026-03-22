<?php

use App\BotCommands\Class\JoinClassCommand;
use App\BotCommands\Conversations\JoinClassConversation;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;

describe('JoinClassCommand (with token)', function () {
    it('successfully joins class with valid token', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        $response = runCommandAs($user, '/joinclass '.$classroom->join_token, [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString($classroom->code, $response['text']);
        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
    });

    it('resets user role to Student after joining', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->admin()->create(['class_id' => null]);
        $this->assertEquals(UserRole::Admin, $user->role);
        runCommandAs($user, '/joinclass '.$classroom->join_token, [JoinClassCommand::class]);
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
        runCommandAs($user, '/joinclass '.$classroom->join_token, [JoinClassCommand::class]);
        $user->refresh();
        $this->assertEquals('Иван', $user->first_name);
        $this->assertEquals('ivan_test', $user->username);
    });

    it('returns error when token does not match any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $response = runCommandAs($user, '/joinclass nonexistent_token_1234', [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Класс не найден', $response['text']);
        $user->refresh();
        $this->assertNull($user->class_id);
    });

    it('returns error when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $response = runCommandAs($user, '/joinclass '.$newClass->join_token, [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Вы уже состоите в классе', $response['text']);
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
        $this->assertNull($user->conversation_state, 'it does not create a new conversation');
    });

    it('sets user class_id correctly', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        runCommandAs($user, '/joinclass '.$classroom->join_token, [JoinClassCommand::class]);
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
        $response = runCommandAs($user, '/joinclass', [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('токен', $response['text']);
        $user->refresh();
        $this->assertEquals('joinclass', $user->getConversationAction());
    });

    it('prompts user to enter token', function () {
        $user = User::factory()->create(['class_id' => null]);
        $response = runCommandAs($user, '/joinclass', [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('already in class returns error instead of starting conversation', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $classroom->id]);
        $response = runCommandAs($user, '/joinclass', [JoinClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Вы уже состоите в классе', $response['text']);
        $user->refresh();
        $this->assertNull($user->getConversationAction());
    });
});

describe('JoinClass conversation validation', function () {
    it('rejects empty input', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'joinclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '', ['joinclass' => JoinClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('токен', $response['text']);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects token that does not match any class', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'joinclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, 'nonexistent_token_1234', ['joinclass' => JoinClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Класс не найден', $response['text']);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('accepts valid token and joins class', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'joinclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, $classroom->join_token, ['joinclass' => JoinClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString($classroom->code, $response['text']);
        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        $this->assertNull($user->conversation_state);
    });

    it('resets role to Student after joining via conversation', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->admin()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'joinclass', 'data' => []],
        ]);
        $this->assertEquals(UserRole::Admin, $user->role);
        sendConversationMessage($user, $classroom->join_token, ['joinclass' => JoinClassConversation::class]);
        $user->refresh();
        $this->assertEquals(UserRole::Student, $user->role);
        $this->assertNull($user->conversation_state);
    });

    it('rejects input when user is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $existingClass->id,
            'conversation_state' => ['action' => 'joinclass', 'data' => []],
        ]);
        $newClass = Classroom::factory()->create();
        $response = sendConversationMessage($user, $newClass->join_token, ['joinclass' => JoinClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Вы уже состоите в классе', $response['text']);
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
    });
});
