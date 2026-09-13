<?php

declare(strict_types=1);

namespace App\Telegram\Conversations\Homework;

use App\Enums\UserRole;
use App\Helpers\ParserService;
use App\Repositories\HomeworkRepository;
use App\Telegram\Conversations\BaseConversation;
use App\Telegram\Menus\DateSelectionMenu;
use App\Telegram\Menus\HomeworkSelectionMenu;
use SergiX44\Nutgram\Nutgram;

class DeleteHomeworkConversation extends BaseConversation
{
    use DateSelectionMenu, HomeworkSelectionMenu;

    public function __construct(private ParserService $parser, private HomeworkRepository $homeworkRepository) {}

    public ?int $userId = null;

    public ?string $dateRange = null;

    public function start(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        if (! in_array($user->role, [UserRole::Teacher, UserRole::Admin, UserRole::OnDuty])) {
            $this->replyAndEnd($bot, __('error.homework.no_permission'));

            return;
        }

        $this->userId = $user->id;

        $keyboard = $this->makeFutureDatesSelectionMenu('deletehomework.date');

        $this->replyAndProceed($bot, __('prompt.homework.select_period'), 'dateSelection', $keyboard);
    }

    public function dateSelection(Nutgram $bot): void
    {
        $range = $this->getCallbackAnswerOrError($bot, 'deletehomework.date', 'dateSelection');

        if ($range === false) {
            return;
        }

        if ($range === 'custom') {
            $this->replyAndProceed($bot, __('prompt.homework.enter_date_format'), 'promptDate');

            return;
        }
        $this->dateRange = $range;

        $this->showHomeworkList($bot);
    }

    public function promptDate(Nutgram $bot): void
    {
        if ($bot->isCallbackQuery()) {
            $this->replyAndProceed($bot, __('prompt.general.enter_date_text'), 'promptDate');

            return;
        }

        $input = $bot->message()->text;

        if ($input === null || trim($input) === '') {
            $this->replyAndProceed($bot, __('error.homework.date_empty'), 'promptDate');

            return;
        }

        $parsed = $this->parser->parseDate($input);
        if ($parsed == null) {
            $this->replyAndProceed($bot, __('error.homework.date_invalid'), 'promptDate');

            return;
        }

        $this->dateRange = $parsed->format('Y-m-d');

        $this->showHomeworkList($bot);
    }

    public function homeworkSelection(Nutgram $bot): void
    {
        $id = $this->getCallbackAnswerOrError($bot, 'deletehomework.select', 'homeworkSelection');

        if ($id === false) {
            return;
        }

        $homeworkId = (int) $id;

        $homework = $this->homeworkRepository->findHomework($homeworkId);

        if ($homework === null) {
            $this->replyAndEnd($bot, __('error.homework.not_found'));

            return;
        }

        $user = $this->getUser($bot);

        if ($homework->class_id !== $user->class_id) {
            $this->replyAndEnd($bot, __('error.homework.not_found_class'));

            return;
        }

        $this->homeworkRepository->deleteHomework($homework->id);

        $this->replyAndEnd($bot, __('info.homework.deleted'));
    }

    private function showHomeworkList(Nutgram $bot): void
    {
        $user = $this->getUser($bot);

        $startDate = $this->dateRange;
        $endDate = $this->dateRange;

        if ($this->dateRange === 'thisweek') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->toDateString();
        } elseif ($this->dateRange === 'nextweek') {
            $startDate = now()->addWeek()->startOfWeek()->toDateString();
            $endDate = now()->addWeek()->endOfWeek()->toDateString();
        } elseif ($this->dateRange === 'tomorrow') {
            $startDate = $endDate = now()->addDay()->toDateString();
        }

        $homeworks = $this->homeworkRepository->getHomeworks($user->class_id, $startDate, $endDate);

        if ($homeworks->isEmpty()) {
            $this->replyAndEnd($bot, __('error.homework.none_in_period'));

            return;
        }

        $keyboard = $this->makeHomeworkSelectionMenu($homeworks, 'deletehomework.select');

        $this->replyAndProceed($bot, __('prompt.homework.select_for_delete'), 'homeworkSelection', $keyboard);
    }
}
