<?php

use App\Enums\UserRole;
use App\Models\Classroom;
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

describe('Start Command (joining a class)', function () {
    it('adds user to the class', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$classroom->join_token)->reply();

        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        assertReplyContains($bot, $classroom->code);
    });

    it('adds user without username and language_code to the class', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->withoutUsername()->withoutLanguageCode()->create(['class_id' => null]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$classroom->join_token)->reply();

        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        $this->assertNull($user->username);
        $this->assertNull($user->language_code);
        assertReplyContains($bot, $classroom->code);
    });

    it('gracefully fails when provided token is invalid', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = botWithData(['token' => 'nonexistent_token_1234'], $user->id, $user->first_name);

        $bot->hearText('/start nonexistent_token_1234')->reply();

        assertReplyContains($bot, __('error.class.not_found'));
        $user->refresh();
        $this->assertNull($user->class_id);
    });

    it('gracefully fails when user is already in a class with correct token (already in a class error)', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $bot = botWithData(['token' => $existingClass->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$existingClass->join_token)->reply();

        assertReplyContains($bot, __('error.class.already_has_class_link'));
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
    });

    it('gracefully fails when user is already in a class with wrong token (already in a class error)', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $bot = botWithData(['token' => $newClass->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$newClass->join_token)->reply();

        assertReplyContains($bot, __('error.class.already_has_class_link'));
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
    });

    it('gracefully fails when user without username and language_code is already in a class', function () {
        $existingClass = Classroom::factory()->create();
        $user = User::factory()->withoutUsername()->withoutLanguageCode()->create(['class_id' => $existingClass->id]);
        $newClass = Classroom::factory()->create();
        $bot = botWithData(['token' => $newClass->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$newClass->join_token)->reply();

        assertReplyContains($bot, __('error.class.already_has_class_link'));
        $user->refresh();
        $this->assertEquals($existingClass->id, $user->class_id);
        $this->assertNull($user->username);
        $this->assertNull($user->language_code);
    });

    it('gracefully fails when class is deleted between token verification and joining', function () {
        $user = User::factory()->create(['class_id' => null]);
        $deletedClass = Classroom::factory()->create();
        $token = $deletedClass->join_token;
        $deletedClass->delete();

        $bot = botWithData(['token' => $token], $user->id, $user->first_name);

        $bot->hearText('/start '.$token)->reply();

        assertReplyContains($bot, __('error.class.not_found'));
        $user->refresh();
        $this->assertNull($user->class_id);
    });

    it('changes class_id and role fields on success', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create(['class_id' => null, 'role' => UserRole::Student]);
        $bot = botWithData(['token' => $classroom->join_token], $user->id, $user->first_name);

        $bot->hearText('/start '.$classroom->join_token)->reply();

        $user->refresh();
        $this->assertEquals($classroom->id, $user->class_id);
        $this->assertEquals(UserRole::Student, $user->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'class_id' => $classroom->id,
            'role' => UserRole::Student,
        ]);
    });

    it('creates new user and joins him in a class', function () {
        $classroom = Classroom::factory()->create();
        $telegramId = 999888777;
        $bot = botWithData(['token' => $classroom->join_token], $telegramId, 'NewUser');

        $bot->hearText('/start '.$classroom->join_token)->reply();

        $this->assertDatabaseHas('users', [
            'id' => $telegramId,
            'first_name' => 'NewUser',
        ]);
        $user = User::findOrFail($telegramId);
        $this->assertEquals($classroom->id, $user->class_id);
        assertReplyContains($bot, $classroom->code);
    });

    it('creates new user even if provided token is invalid', function () {
        $telegramId = 777666555;
        $bot = botWithData(['token' => 'bad_token'], $telegramId, 'GhostUser');

        $bot->hearText('/start bad_token')->reply();

        $this->assertDatabaseHas('users', [
            'id' => $telegramId,
            'first_name' => 'GhostUser',
        ]);
        $user = User::findOrFail($telegramId);
        $this->assertNull($user->class_id);
        assertReplyContains($bot, __('error.class.not_found'));
    });
});
