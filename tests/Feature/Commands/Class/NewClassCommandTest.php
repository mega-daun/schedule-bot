<?php

use App\BotCommands\CancelCommand;
use App\BotCommands\Class\NewClassCommand;
use App\BotCommands\Conversations\NewClassConversation;
use App\Models\Classroom;
use App\Models\User;

describe('NewClass command', function () {
    it('starts conversation and prompts for class name', function () {
        $user = User::factory()->create(['class_id' => null]);
        $response = runCommandAs($user, '/newclass', [NewClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('название', $response['text']);
        $user->refresh();
        $this->assertEquals('newclass', $user->getConversationAction());
    });

    it('returns error when user already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $response = runCommandAs($user, '/newclass', [NewClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Вы уже состоите в классе', $response['text']);
    });
});

describe('NewClass conversation validation', function () {
    it('rejects empty input', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('название', $response['text']);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects input without Russian letter', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '10', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects input starting with letter', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, 'А1', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects input with too many digits', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '123Б', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects invalid digit after 10', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '12А', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });

    it('rejects input that is too long', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '101АБ', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $user->refresh();
        $this->assertNotNull($user->conversation_state);
    });
});

describe('NewClass conversation valid inputs', function () {
    it('creates class with single digit and letter', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '5Г', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('5Г', $response['text']);
        $this->assertDatabaseHas('classes', ['code' => '5Г']);
        $class = Classroom::where('code', '5Г')->first();
        $this->assertNotNull($class);
        $this->assertStringContainsString($class->join_token, $response['text']);
    });

    it('creates class with two digits and letter', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '10Б', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('10Б', $response['text']);
        $this->assertDatabaseHas('classes', ['code' => '10Б']);
    });

    it('creates class with lowercase letter', function () {
        $user = User::factory()->create([
            'class_id' => null,
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = sendConversationMessage($user, '11в', ['newclass' => NewClassConversation::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('11В', $response['text']);
        $user->refresh();
        $this->assertNull($user->conversation_state);
    });
});

describe('NewClass full conversation flow', function () {
    it('completes class creation and cancel works afterwards', function () {
        $user = User::factory()->create(['class_id' => null]);
        runCommandAs($user, '/newclass', [NewClassCommand::class]);
        $user->refresh();
        $this->assertEquals('newclass', $user->getConversationAction());
        $response = sendConversationMessage($user, '10Б', ['newclass' => NewClassConversation::class]);
        $this->assertStringContainsString('10Б', $response['text']);
        $user->refresh();
        $this->assertNotNull($user->class_id);
        $this->assertNull($user->conversation_state);
        $cancelResponse = runCommandAs($user, '/cancel', [CancelCommand::class]);
        $this->assertStringContainsString('Нет активных действий', $cancelResponse['text']);
    });
});
