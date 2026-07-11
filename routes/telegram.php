<?php

declare(strict_types=1);

/** @var Nutgram $bot */

use App\Http\Middleware\HasClassMembersMiddleware;
use App\Http\Middleware\HasClassMiddleware;
use App\Http\Middleware\HasOnDutyRoleMiddleware;
use App\Http\Middleware\HasSubjectsMiddleware;
use App\Http\Middleware\IncorrectMessageMiddleware;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\NoClassMiddleware;
use App\Telegram\Commands\CancelCommand;
use App\Telegram\Commands\Class\ChangeRoleCommand;
use App\Telegram\Commands\Class\DeleteClassCommand;
use App\Telegram\Commands\Class\JoinClassCommand;
use App\Telegram\Commands\Class\LeaveClassCommand;
use App\Telegram\Commands\Class\NewClassCommand;
use App\Telegram\Commands\StartCommand;
use App\Telegram\Conversations\Class\ChangeRoleConversation;
use App\Telegram\Conversations\Class\JoinClassConversation;
use App\Telegram\Conversations\Class\NewClassConversation;
use App\Telegram\Conversations\Homework\DeleteHomeworkConversation;
use App\Telegram\Conversations\Homework\NewHomeworkConversation;
use App\Telegram\Conversations\Homework\ShowHomeworkConversation;
use App\Telegram\Conversations\Subject\DeleteSubjectConversation;
use App\Telegram\Conversations\Subject\NewSubjectConversation;

$bot->middleware(IncorrectMessageMiddleware::class);

$bot->onCommand('start {token}', StartCommand::class)->description(__('command_descriptions.cmd.start'));
$bot->onCommand('start', StartCommand::class)->description(__('command_descriptions.cmd.start'));
$bot->onCommand('cancel', CancelCommand::class)->description(__('command_descriptions.cmd.cancel'));

$bot->onCommand('newclass {code}', NewClassCommand::class)->middleware(NoClassMiddleware::class)->description(__('command_descriptions.cmd.newclass'));
$bot->onCommand('newclass', NewClassConversation::class)->middleware(NoClassMiddleware::class)->description(__('command_descriptions.cmd.newclass_step'));

$bot->onCommand('joinclass {token}', JoinClassCommand::class)->middleware(NoClassMiddleware::class)->description(__('command_descriptions.cmd.joinclass'));
$bot->onCommand('joinclass', JoinClassConversation::class)->middleware(NoClassMiddleware::class)->description(__('command_descriptions.cmd.joinclass_step'));

$bot->onCommand('deleteclass', DeleteClassCommand::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMiddleware::class)
    ->description(__('command_descriptions.cmd.deleteclass'));

$bot->onCommand('changerole {username} {role}', ChangeRoleCommand::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMiddleware::class)
    ->description(__('command_descriptions.cmd.changerole'));

$bot->onCommand('changerole', ChangeRoleConversation::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMembersMiddleware::class)
    ->description(__('command_descriptions.cmd.changerole_step'));

$bot->onCommand('leaveclass', LeaveClassCommand::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.leaveclass'));

$bot->onCommand('newhomework', NewHomeworkConversation::class)->middleware(HasSubjectsMiddleware::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.newhomework'));

$bot->onCommand('deletehomework', DeleteHomeworkConversation::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.deletehomework'));

$bot->onCommand('showhomework', ShowHomeworkConversation::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.showhomework'));

$bot->onCommand('newsubject', NewSubjectConversation::class)->middleware(HasOnDutyRoleMiddleware::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.newsubject'));

$bot->onCommand('deletesubject', DeleteSubjectConversation::class)->middleware(HasSubjectsMiddleware::class)->middleware(HasOnDutyRoleMiddleware::class)->middleware(HasClassMiddleware::class)->description(__('command_descriptions.cmd.deletesubject'));
