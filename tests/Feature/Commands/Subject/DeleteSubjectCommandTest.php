<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;

describe('DeleteSubject command', function () {
    it('returns error when user has no class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Вы не состоите в классе');
    });

    it('returns error when user role is Student', function () {
        $class = Classroom::factory()->has(Subject::factory(3))->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'ученик',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Вы не имеете право это сделать');
    });

    it('returns message when class has no subjects for OnDuty', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'дежурный',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'нет предметов');
    });

    it('returns message when class has no subjects for Teacher', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'учитель',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'нет предметов');
    });

    it('returns message when class has no subjects for Admin', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'нет предметов');
    });

    it('shows subject list with inline keyboard for OnDuty', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Физика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'дежурный',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Математика');
        assertReplyContains($bot, 'Физика');
    });

    it('shows subject list with inline keyboard for Teacher', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'учитель',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Математика');
    });

    it('shows subject list with inline keyboard for Admin', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Математика');
    });

    it('shows subject list with multiple subjects', function () {
        $class = Classroom::factory()->create();
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Физика']);
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Химия']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'Математика');
        assertReplyContains($bot, 'Физика');
        assertReplyContains($bot, 'Химия');
    });
});

describe('DeleteSubject callback - actual deletion', function () {
    it('deletes subject from database', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);

        $bot->willStartConversation(true)->hearText('/deletesubject')->reply();
        $bot->hearCallbackQueryData('deletesubject.select.'.$subject->id)->reply();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    });

    it('deletes one subject and keeps others', function () {
        $class = Classroom::factory()->create();
        $subject1 = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        Subject::factory()->create(['class_id' => $class->id, 'name' => 'Физика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(true)->hearText('/deletesubject')->reply();
        $bot->hearCallbackQueryData('deletesubject.select.'.$subject1->id)->reply();
        expect(Subject::count())->toBe(1);
        expect(Subject::first()->name)->toBe('Физика');
    });

    it('handles non-existent subject id gracefully', function () {
        $class = Classroom::factory()->has(Subject::factory(3))->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(true)->hearText('/deletesubject')->reply();
        $bot->hearCallbackQueryData('deletesubject.select.999')->reply();
        assertReplyContains($bot, 'не найден');
    });

    it('works for Teacher role', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'учитель',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(true)->hearText('/deletesubject')->reply();
        $bot->hearCallbackQueryData('deletesubject.select.'.$subject->id)->reply();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    });

    it('works for OnDuty role', function () {
        $class = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $class->id, 'name' => 'Математика']);
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'дежурный',
        ]);
        $bot = bot($user);
        $bot->willStartConversation(true)->hearText('/deletesubject')->reply();
        $bot->hearCallbackQueryData('deletesubject.select.'.$subject->id)->reply();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    });
});

describe('DeleteSubject cancel', function () {
    it('shows message when no subjects available', function () {
        $class = Classroom::factory()->create();
        $user = User::factory()->create([
            'class_id' => $class->id,
            'role' => 'админ',
        ]);
        $bot = bot($user);
        $bot->hearText('/deletesubject')->reply();
        assertReplyContains($bot, 'нет предметов');
    });
});
