<?php

declare(strict_types=1);

/** @var Nutgram $bot */

use App\BotCommands\Subject\DeleteSubjectConversation;
use App\BotCommands\Subject\NewSubjectConversation;
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

$bot->onCommand('newhomework', NewHomeworkConversation::class)->description('Добавить домашнее задание');

$bot->onCommand('deletehomework', DeleteHomeworkConversation::class)->description('Удалить домашнее задание');

$bot->onCommand('showhomework', ShowHomeworkConversation::class)->description('Показать домашнее задание');

