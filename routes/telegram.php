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
use App\BotCommands\Conversations\NewHomeworkConversation;
use App\BotCommands\StartCommand;
use App\Http\Middleware\IncorrectMessageMiddleware;

$bot->middleware(IncorrectMessageMiddleware::class);

$bot->onCommand('start {token}', StartCommand::class)->description('Начинает общение с ботом');
$bot->onCommand('start', StartCommand::class)->description('Начинает общение с ботом');
$bot->onCommand('cancel', CancelCommand::class)->description('Отменяет текущее действие');

$bot->onCommand('newclass {code}', NewClassCommand::class)->description('Создать новый класс');
$bot->onCommand('newclass', NewClassConversation::class)->description('Создать новый класс (пошагово)');

$bot->onCommand('joinclass {token}', JoinClassCommand::class)->description('Присоедениться к классу');
$bot->onCommand('joinclass', JoinClassConversation::class)->description('Присоедениться к классу (пошагово)');

$bot->onCommand('deleteclass', DeleteClassCommand::class)->description('Удалить свой класс');

$bot->onCommand('changerole {username} {role}', ChangeRoleCommand::class)->description('Изменить роль участнику класса');
$bot->onCommand('changerole', ChangeRoleConversation::class)->description('Изменить роль участнику класса(пошагово)');

$bot->onCommand('leaveclass', LeaveClassCommand::class)->description('Выйти из класса');

$bot->onCommand('newhomework', NewHomeworkConversation::class)->description('Добавить домашнее задание');
