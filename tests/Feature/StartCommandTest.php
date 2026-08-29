<?php

use App\Models\User;

describe('StartCommand (registration)', function () {
    it('registers new user with username', function () {
        $telegramId = 123456789;
        $bot = botWith(id: $telegramId, firstName: 'Иван', username: 'ivan');

        $bot->hearText('/start')->reply();

        $this->assertDatabaseHas('users', [
            'id' => $telegramId,
            'first_name' => 'Иван',
            'username' => 'ivan',
        ]);
    });

    it('registers new user without username', function () {
        $telegramId = 987654321;
        $bot = botWith(id: $telegramId, firstName: 'Мария', username: null);

        $bot->hearText('/start')->reply();

        $this->assertDatabaseHas('users', [
            'id' => $telegramId,
            'first_name' => 'Мария',
            'username' => null,
        ]);

        $user = User::findOrFail($telegramId);
        $this->assertNull($user->username);
    });

    it('registers new user without language_code', function () {
        $telegramId = 111222333;
        $bot = botWith(id: $telegramId, firstName: 'Пётр', username: 'petr', languageCode: null);

        $bot->hearText('/start')->reply();

        $user = User::findOrFail($telegramId);
        $this->assertNull($user->language_code);
    });

    it('registers user with both username and language_code null', function () {
        $telegramId = 444555666;
        $bot = botWith(id: $telegramId, firstName: 'Алексей', username: null, languageCode: null);

        $bot->hearText('/start')->reply();

        $user = User::findOrFail($telegramId);
        $this->assertNull($user->username);
        $this->assertNull($user->language_code);
    });

    it('finds existing user on re-start instead of creating duplicate', function () {
        $user = User::factory()->create(['username' => 'existing_user']);

        $bot = botWith(id: $user->id, firstName: $user->first_name, username: $user->username);
        $bot->hearText('/start')->reply();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'existing_user',
        ]);
    });

    it('finds existing user without username on re-start', function () {
        $user = User::factory()->withoutUsername()->create();

        $bot = botWith(id: $user->id, firstName: $user->first_name, username: null);
        $bot->hearText('/start')->reply();

        $this->assertDatabaseCount('users', 1);
        $this->assertNull($user->fresh()->username);
    });

    it('sends welcome message on start', function () {
        $bot = botWith(id: 123456789, firstName: 'Иван', username: 'ivan');

        $bot->hearText('/start')->reply();

        assertReplyContains($bot, 'Иван');
    });
});
