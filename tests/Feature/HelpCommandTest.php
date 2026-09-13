<?php

use App\Models\Classroom;
use App\Models\User;

describe('HelpCommand', function () {
    it('shows base and no-class commands for a user without a class', function () {
        $user = User::factory()->create(['class_id' => null]);
        $bot = bot($user);

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, __('info.help.title'));
        assertReplyContains($bot, '/start');
        assertReplyContains($bot, '/cancel');
        assertReplyContains($bot, '/help');
        assertReplyContains($bot, __('command_descriptions.cmd.start'));
        assertReplyContains($bot, __('command_descriptions.cmd.cancel'));
        assertReplyContains($bot, __('command_descriptions.cmd.help'));
        assertReplyContains($bot, '/newclass');
        assertReplyContains($bot, __('command_descriptions.cmd.newclass'));
        assertReplyContains($bot, '/joinclass');
        assertReplyContains($bot, __('command_descriptions.cmd.joinclass'));

        assertReplyMarkupNotContains($bot, [
            '/showhomework',
            '/leaveclass',
            '/newhomework',
            '/deletehomework',
            '/newsubject',
            '/deletesubject',
            '/newschedule',
            '/deleteclass',
            '/changerole',
        ]);
    });

    it('shows base and class commands for a student in class', function () {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->student()->create(['class_id' => $classroom->id]);
        $bot = bot($student);

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, '/showhomework');
        assertReplyContains($bot, __('command_descriptions.cmd.showhomework'));
        assertReplyContains($bot, '/leaveclass');
        assertReplyContains($bot, __('command_descriptions.cmd.leaveclass'));
        assertReplyContains($bot, '/newhomework');
        assertReplyContains($bot, __('command_descriptions.cmd.newhomework'));
        assertReplyContains($bot, '/deletehomework');
        assertReplyContains($bot, __('command_descriptions.cmd.deletehomework'));


        assertReplyMarkupNotContains($bot, [
            '/newsubject',
            '/deletesubject',
            '/newschedule',
            '/deleteclass',
            '/changerole',
            '/newclass',
            '/joinclass',
        ]);
    });

    it('shows duty-level commands for an on-duty user in class', function () {
        $classroom = Classroom::factory()->create();
        $onDuty = User::factory()->onDuty()->create(['class_id' => $classroom->id]);
        $bot = bot($onDuty);

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, '/newhomework');
        assertReplyContains($bot, '/deletehomework');
        assertReplyContains($bot, '/newsubject');
        assertReplyContains($bot, '/deletesubject');
        assertReplyContains($bot, '/newschedule');
        assertReplyContains($bot, __('command_descriptions.cmd.newschedule'));

        assertReplyMarkupNotContains($bot, [
            '/deleteclass',
            '/changerole',
        ]);
    });

    it('shows duty-level commands for a teacher in class but not admin-only', function () {
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->teacher()->create(['class_id' => $classroom->id]);
        $bot = bot($teacher);

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, '/newhomework');
        assertReplyContains($bot, '/deletehomework');
        assertReplyContains($bot, '/newsubject');
        assertReplyContains($bot, '/deletesubject');
        assertReplyContains($bot, '/newschedule');

        assertReplyMarkupNotContains($bot, [
            '/deleteclass',
            '/changerole',
        ]);
    });

    it('shows duty-level and admin commands for an admin in class', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, '/newhomework');
        assertReplyContains($bot, '/newschedule');
        assertReplyContains($bot, '/deleteclass');
        assertReplyContains($bot, __('command_descriptions.cmd.deleteclass'));
        assertReplyContains($bot, '/changerole');
        assertReplyContains($bot, __('command_descriptions.cmd.changerole'));

        assertReplyMarkupNotContains($bot, [
            '/newclass',
            '/joinclass',
        ]);
    });

    it('shows no-class commands for a user not registered in the database', function () {
        $bot = botWith(id: 777555333, firstName: 'Гость');

        $bot->hearText('/help')->reply();

        assertReplyContains($bot, __('info.help.title'));
        assertReplyContains($bot, '/newclass');
        assertReplyContains($bot, '/joinclass');

        assertReplyMarkupNotContains($bot, [
            '/showhomework',
            '/leaveclass',
            '/newhomework',
            '/newschedule',
            '/deleteclass',
            '/changerole',
        ]);
    });

    it('never shows step variants of commands', function () {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create(['class_id' => $classroom->id]);
        $bot = bot($admin);

        $bot->hearText('/help')->reply();

        assertReplyMarkupNotContains($bot, [
            'пошагово',
            'newclass_step',
            'joinclass_step',
            'changerole_step',
        ]);
    });
});
