<?php

declare(strict_types=1);

/** @var Nutgram $bot */

use App\BotCommands\CancelCommand;
use App\BotCommands\Class\ChangeRoleCommand;
use App\BotCommands\Class\DeleteClassCommand;
use App\BotCommands\Class\LeaveClassCommand;
use App\BotCommands\Conversations\JoinClassConversation;
use App\BotCommands\Conversations\NewClassConversation;
use App\BotCommands\Homework\DeleteHomeworkCommand;
use App\BotCommands\Homework\NewHomeworkCommand;
use App\BotCommands\Schedule\NewScheduleCommand;
use App\BotCommands\StartCommand;
use App\BotCommands\Subject\DeleteSubjectCommand;
use App\BotCommands\Subject\NewSubjectCommand;
use App\Http\Middleware\IncorrectMessageMiddleware;
use SergiX44\Nutgram\Nutgram;

$bot->middleware(IncorrectMessageMiddleware::class);

$bot->onCommand('start', StartCommand::class)->description('Начинает общение с ботом');
$bot->onCommand('cancel', CancelCommand::class)->description('Отменяет текущее действие');
$bot->onCommand('newclass', NewClassConversation::class)->description('Создать новый класс');
$bot->onCommand('joinclass', JoinClassConversation::class)->description('Присоедениться к классу');
$bot->onCommand('deleteclass', DeleteClassCommand::class)->description('Удалить класс');
$bot->onCommand('leaveclass', LeaveClassCommand::class)->description('Покинуть класс');
$bot->onCommand('changerole', ChangeRoleCommand::class)->description('Изменить роль пользователя');
$bot->onCommand('newsubject', NewSubjectCommand::class)->description('Добавить предмет');
$bot->onCommand('deletesubject', DeleteSubjectCommand::class)->description('Удалить предмет');
$bot->onCommand('newschedule', NewScheduleCommand::class)->description('Добавить расписание');
$bot->onCommand('newhomework', NewHomeworkCommand::class)->description('Добавить домашнее задание');
$bot->onCommand('deletehomework', DeleteHomeworkCommand::class)->description('Удалить домашнее задание');
