<?php

use App\BotCommands\CancelCommand;
use App\Models\User;

describe('Cancel command', function () {
    it('returns error when no active conversation', function () {
        $user = User::factory()->create(['conversation_state' => null]);
        $response = runCommandAs($user, '/cancel', [CancelCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Нет активных действий для отмены', $response['text']);
    });

    it('cancels active conversation and returns action name', function () {
        $user = User::factory()->create([
            'conversation_state' => ['action' => 'newclass', 'data' => ['step' => 1]],
        ]);
        $response = runCommandAs($user, '/cancel', [CancelCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('newclass', $response['text']);
    });

    it('clears conversation state in database', function () {
        $user = User::factory()->create([
            'conversation_state' => ['action' => 'newclass', 'data' => []],
        ]);
        $response = runCommandAs($user, '/cancel', [CancelCommand::class]);
        $user->refresh();
        $this->assertNull($user->conversation_state);
    });
});
