<?php

declare(strict_types=1);

/** @var Nutgram $bot */

use App\BotCommands\CancelCommand;
use App\BotCommands\Class\ChangeRoleCommand;
use App\BotCommands\Class\DeleteClassCommand;
use App\BotCommands\Class\JoinClassCommand;
use App\BotCommands\Class\LeaveClassCommand;
use App\BotCommands\Class\NewClassCommand;
use App\BotCommands\Conversations\ChangeRoleConversation;
use App\BotCommands\Conversations\JoinClassConversation;
use App\BotCommands\Conversations\NewClassConversation;
use App\BotCommands\Homework\DeleteHomeworkCommand;
use App\BotCommands\Homework\NewHomeworkCommand;
use App\BotCommands\Schedule\NewScheduleCommand;
use App\BotCommands\StartCommand;
use App\BotCommands\Subject\DeleteSubjectConversation;
use App\BotCommands\Subject\NewSubjectConversation;
use App\Http\Middleware\HasClassMembersMiddleware;
use App\Http\Middleware\HasClassMiddleware;
use App\Http\Middleware\HasOnDutyRoleMiddleware;
use App\Http\Middleware\HasSubjectsMiddleware;
use App\Http\Middleware\IncorrectMessageMiddleware;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\NoClassMiddleware;

$bot->middleware(IncorrectMessageMiddleware::class);

$bot->onCommand('start {token}', StartCommand::class)->description('Начинает общение с ботом');
$bot->onCommand('start', StartCommand::class)->description('Начинает общение с ботом');
$bot->onCommand('cancel', CancelCommand::class)->description('Отменяет текущее действие');

$bot->onCommand('newclass {code}', NewClassCommand::class)->middleware(NoClassMiddleware::class)->description('Создать новый класс');
$bot->onCommand('newclass', NewClassConversation::class)->middleware(NoClassMiddleware::class)->description('Создать новый класс (пошагово)');

$bot->onCommand('joinclass {token}', JoinClassCommand::class)->middleware(NoClassMiddleware::class)->description('Присоедениться к классу');
$bot->onCommand('joinclass', JoinClassConversation::class)->middleware(NoClassMiddleware::class)->description('Присоедениться к классу (пошагово)');

$bot->onCommand('deleteclass', DeleteClassCommand::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMiddleware::class)
    ->description('Удалить свой класс');

$bot->onCommand('changerole {username} {role}', ChangeRoleCommand::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMiddleware::class)
    ->description('Изменить роль участнику класса');

$bot->onCommand('changerole', ChangeRoleConversation::class)
    ->middleware(IsAdminMiddleware::class)
    ->middleware(HasClassMembersMiddleware::class)
    ->description('Изменить роль участнику класса(пошагово)');

$bot->onCommand('leaveclass', LeaveClassCommand::class)->middleware(HasClassMiddleware::class)->description('Выйти из класса');

$bot->onCommand('newsubject', NewSubjectConversation::class)
    ->middleware(HasOnDutyRoleMiddleware::class)
    ->description('Добавить предмет');
$bot->onCommand('deletesubject', DeleteSubjectConversation::class)
    ->middleware(HasSubjectsMiddleware::class)
    ->middleware(HasOnDutyRoleMiddleware::class)
    ->description('Удалить предмет');

$bot->onCallbackQueryData('deletesubject.select.', DeleteSubjectConversation::class);

$bot->onCommand('newschedule', NewScheduleCommand::class)->description('Добавить расписание');
$bot->onCommand('newhomework', NewHomeworkCommand::class)->description('Добавить домашнее задание');
$bot->onCommand('deletehomework', DeleteHomeworkCommand::class)->description('Удалить домашнее задание');
