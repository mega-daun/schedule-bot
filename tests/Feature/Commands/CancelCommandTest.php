<?php

use App\Models\User;

describe('Cancel command', function () {
    it('returns error when no active conversation', function () {
        $user = User::factory()->create();
        $bot = bot($user);
        $bot->hearText('/cancel')->reply();
        assertReplyContains($bot, 'Нет активных действий для отмены');
        $bot->assertNoConversation($user->id, $user->id);
    });

    it('cancels active conversation and returns action name', function () {
        $user = User::factory()->create();
        $bot = bot($user);

        $bot->willStartConversation(remember: true)
            ->hearText('/cancel')
            ->reply();
        $bot->assertNoConversation();
    });
});
