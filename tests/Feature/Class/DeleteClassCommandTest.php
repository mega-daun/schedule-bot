<?php

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyScheduleEntry;

describe('DeleteClassCommand', function () {
    it('admin can successfully delete their class', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, $classroom->code);
        $this->assertDatabaseMissing('classes', ['id' => $classroom->id]);
    });

    it('returns success message with class code', function () {
        $classroom = Classroom::factory()->create(['code' => '10Б']);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, '10Б');
    });

    it('non-admin (Student) returns permission error', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        $bot = bot($student);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
        $this->assertDatabaseHas('classes', ['id' => $classroom->id]);
    });

    it('non-admin (Teacher) returns permission error', function () {
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->teacher()->create(['class_id' => $classroom->id]);
        $bot = bot($teacher);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
        $this->assertDatabaseHas('classes', ['id' => $classroom->id]);
    });

    it('non-admin (OnDuty) returns permission error', function () {
        $classroom = Classroom::factory()->create();
        $onDuty = User::factory()->onDuty()->create(['class_id' => $classroom->id]);
        $bot = bot($onDuty);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, __('error.class.no_permission'));
        $this->assertDatabaseHas('classes', ['id' => $classroom->id]);
    });

    it('returns error when user is not in any class', function () {
        $user = User::factory()->admin()->create(['class_id' => null]);
        $bot = bot($user);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, __('error.class.not_member'));
    });

    it('deletes associated homework records', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $classroom->id]);
        Homework::factory()->count(5)->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $this->assertDatabaseMissing('homeworks', ['class_id' => $classroom->id]);
    });

    it('deletes associated weekly schedule entries', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $classroom->id]);
        WeeklyScheduleEntry::factory()->count(4)->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $this->assertDatabaseMissing('weekly_schedule_entries', ['class_id' => $classroom->id]);
        $this->assertDatabaseMissing('subjects', ['class_id' => $classroom->id]);
    });

    it('users remain in database after class deletion', function () {
        $classroom = Classroom::factory()->create();
        $users = User::factory()->count(3)->create(['class_id' => $classroom->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $userIds = $users->pluck('id')->toArray();
        $userIds[] = $admin->id;
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        foreach ($userIds as $id) {
            $this->assertDatabaseHas('users', ['id' => $id]);
        }
    });

    it('users class_id becomes null after class deletion', function () {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->student()->create(['class_id' => $classroom->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $user->refresh();
        $admin->refresh();
        $this->assertNull($user->class_id);
        $this->assertNull($admin->class_id);
    });

    it('users role resets to Student after class deletion', function () {
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->teacher()->create(['class_id' => $classroom->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $teacher->refresh();
        $admin->refresh();
        $this->assertEquals(UserRole::Student, $teacher->role);
        $this->assertEquals(UserRole::Student, $admin->role);
    });

    it('all users in class get reset to Student role', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $teacher = User::factory()->teacher()->create(['class_id' => $classroom->id]);
        $onDuty = User::factory()->onDuty()->create(['class_id' => $classroom->id]);
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $teacher->refresh();
        $onDuty->refresh();
        $student->refresh();
        $this->assertEquals(UserRole::Student, $teacher->role);
        $this->assertEquals(UserRole::Student, $onDuty->role);
        $this->assertEquals(UserRole::Student, $student->role);
    });

    it('handles deletion of class with no other users', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        assertReplyContains($bot, $classroom->code);
        $this->assertDatabaseMissing('classes', ['id' => $classroom->id]);
        $admin->refresh();
        $this->assertNull($admin->class_id);
        $this->assertEquals(UserRole::Student, $admin->role);
    });

    it('handles deletion of class with multiple users', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $users = User::factory()->count(5)->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->hearText('/deleteclass')->reply();
        $this->assertDatabaseMissing('classes', ['id' => $classroom->id]);
        $this->assertEquals(0, User::whereIn('id', $users->pluck('id'))->whereNotNull('class_id')->count());
    });

    it('class deletion clears any active conversations for other users', function () {
        $classroom = Classroom::factory()->create();
        $otherUser = User::factory()->create(['class_id' => $classroom->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);
        $bot->willStartConversation(remember: true)
            ->hearText('/deleteclass')
            ->reply();
        $bot->assertNoConversation();
    });
});
