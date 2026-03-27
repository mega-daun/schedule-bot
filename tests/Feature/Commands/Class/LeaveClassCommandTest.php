<?php

use App\BotCommands\Class\LeaveClassCommand;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyScheduleEntry;

describe('LeaveClassCommand', function () {
    it('student can successfully leave their class', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        $response = runCommandAs($student, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $student->refresh();
        $this->assertNull($student->class_id);
    });

    it('sets user class_id to null', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        runCommandAs($student, '/leaveclass', [LeaveClassCommand::class]);
        $student->refresh();
        $this->assertNull($student->class_id);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'class_id' => null]);
    });

    it('resets user role to Student', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        runCommandAs($student, '/leaveclass', [LeaveClassCommand::class]);
        $student->refresh();
        $this->assertEquals(UserRole::Student, $student->role);
    });

    it('clears conversation_state', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create([
            'class_id' => $classroom->id,
            'conversation_state' => ['action' => 'some_action', 'data' => []],
        ]);
        runCommandAs($student, '/leaveclass', [LeaveClassCommand::class]);
        $student->refresh();
        $this->assertNull($student->conversation_state);
    });

    it('returns success message', function () {
        $classroom = Classroom::factory()->create(['code' => '9А']);
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        $response = runCommandAs($student, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('9А', $response['text']);
    });

    it('returns error when user is not in any class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $response = runCommandAs($user, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertArrayHasKey('text', $response);
        $this->assertStringContainsString('Вы не состоите в классе', $response['text']);
    });

    it('other users in class are not affected', function () {
        $classroom = Classroom::factory()->create();
        $leaver = User::factory()->student()->create(['class_id' => $classroom->id]);
        $otherUser = User::factory()->student()->create(['class_id' => $classroom->id]);
        $otherUserId = $otherUser->id;
        runCommandAs($leaver, '/leaveclass', [LeaveClassCommand::class]);
        $otherUser->refresh();
        $this->assertEquals($classroom->id, $otherUser->class_id);
    });

    it('other users class_id remains unchanged', function () {
        $classroom = Classroom::factory()->create();
        $leaver = User::factory()->student()->create(['class_id' => $classroom->id]);
        $otherUser = User::factory()->student()->create(['class_id' => $classroom->id]);
        runCommandAs($leaver, '/leaveclass', [LeaveClassCommand::class]);
        $otherUser->refresh();
        $this->assertEquals($classroom->id, $otherUser->class_id);
    });
});

describe('LeaveClassCommand - Admin leaving with other members', function () {
    it('admin leaves but class remains', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $otherUser = User::factory()->student()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertDatabaseHas('classes', ['id' => $classroom->id]);
    });

    it('admin leaves and random user becomes Admin', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $users = User::factory()->count(3)->create(['class_id' => $classroom->id]);
        $userIds = $users->pluck('id')->toArray();
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $adminsAfter = User::whereIn('id', $userIds)->where('role', UserRole::Admin)->count();
        $this->assertEquals(1, $adminsAfter);
    });

    it('original admin role resets to Student', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        User::factory()->count(2)->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $admin->refresh();
        $this->assertEquals(UserRole::Student, $admin->role);
    });

    it('admin leaves and class still has exactly one admin', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $users = User::factory()->count(5)->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertEquals(1, User::where('class_id', $classroom->id)->where('role', UserRole::Admin)->count());
    });

    it('admin leaves and conversation_state is cleared for leaver', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create([
            'class_id' => $classroom->id,
            'conversation_state' => ['action' => 'some_action', 'data' => []],
        ]);
        User::factory()->count(2)->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $admin->refresh();
        $this->assertNull($admin->conversation_state);
    });

    it('admin leaves with 2+ users and one random user is new admin', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $users = User::factory()->count(2)->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertEquals(1, User::where('class_id', $classroom->id)->where('role', UserRole::Admin)->count());
    });

    it('admin leaves with many users and exactly one new admin assigned', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        User::factory()->count(10)->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertEquals(1, User::where('class_id', $classroom->id)->where('role', UserRole::Admin)->count());
    });
});

describe('LeaveClassCommand - Admin as only member', function () {
    it('admin is only member and class is deleted', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertDatabaseMissing('classes', ['id' => $classroom->id]);
    });

    it('admin is only member and subjects are deleted', function () {
        $classroom = Classroom::factory()->create();
        Subject::factory()->count(3)->create(['class_id' => $classroom->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertDatabaseMissing('subjects', ['class_id' => $classroom->id]);
    });

    it('admin is only member and homework is deleted', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $classroom->id]);
        Homework::factory()->count(5)->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertDatabaseMissing('homeworks', ['class_id' => $classroom->id]);
    });

    it('admin is only member and schedules are deleted', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['class_id' => $classroom->id]);
        WeeklyScheduleEntry::factory()->count(4)->create(['class_id' => $classroom->id, 'subject_id' => $subject->id]);
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $this->assertDatabaseMissing('weekly_schedule_entries', ['class_id' => $classroom->id]);
    });

    it('admin is only member and user class_id becomes null', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $admin->refresh();
        $this->assertNull($admin->class_id);
    });

    it('admin is only member and user role resets to Student', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $admin->refresh();
        $this->assertEquals(UserRole::Student, $admin->role);
    });

    it('admin is only member and user conversation_state is cleared', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create([
            'class_id' => $classroom->id,
            'conversation_state' => ['action' => 'some_action', 'data' => []],
        ]);
        runCommandAs($admin, '/leaveclass', [LeaveClassCommand::class]);
        $admin->refresh();
        $this->assertNull($admin->conversation_state);
    });
});
