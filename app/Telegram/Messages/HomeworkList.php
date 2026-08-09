<?php

declare(strict_types=1);

namespace App\Telegram\Messages;

use App\DataObjects\Schedule\Lesson;
use App\DataObjects\Schedule\Schedule;
use App\Models\Homework;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait HomeworkList
{
    /**
     * Render the homework list for a single day according to the schedule.
     *
     * The lessons shown are those of the schedule for the weekday of $date.
     * Homework for a subject is looked up by the exact date of $date, so records
     * for any other date are ignored.
     *
     * When $date is not a workday of the schedule, a generic "no homework"
     * message is returned instead of a list.
     *
     * @param  Carbon  $date  The day to render. Its weekday (1 = Monday, 7 = Sunday)
     *                        selects the lessons from the schedule.
     * @param  Schedule  $schedule  Schedule containing the lessons for each weekday.
     * @param  Collection<int, Homework>  $homeworks  Homework records for the given
     *                                                date only — the caller must pre-filter them (e.g. with
     *                                                `whereDate('date', $date)`). Records on other dates are
     *                                                never rendered.
     * @return string The rendered message.
     */
    public function makeHomeworkListOnDay(Carbon $date, Schedule $schedule, Collection $homeworks): string
    {
        if (! $schedule->hasWorkday($date->isoWeekday())) {
            return __('info.homework.no_homework');
        }
        $homeworks = $this->mapHomeworksToDaysSubjects($homeworks);
        $params = [
            'date' => $date->format('d.m'),
            'lessons' => $schedule->getLessons($date->isoWeekday())
                ->map(
                    fn (Lesson $ls) => [
                        'subject' => $ls->getSubjectName(),
                        'homework' => $this->getHomework($date, $ls->getSubjectId(), $homeworks),
                    ]
                ),
        ];

        return view('messages/Homework/daily_homework_view', $params)->render();
    }

    /**
     * Render the homework list for the rest of the week starting from $start.
     *
     * The rendered range runs from the weekday of $start through Sunday of the
     * same week. $start itself is used only to derive the week: its Monday is
     * computed via `startOfWeek(Carbon::MONDAY)` and anchors the date matching.
     * Homework is picked up by the actual calendar date each weekday falls on
     * (weekday 3 → Wednesday of that week), not by the weekday number.
     *
     * @param  Carbon  $start  Any day within the target week; the weekday of $start
     *                         is the first day shown, and its week determines which
     *                         days come after it.
     * @param  Schedule  $schedule  Schedule containing the lessons for each weekday.
     * @param  Collection<int, Homework>  $homeworks  Homework records for the whole
     *                                                week (Monday–Sunday of $start's week), pre-filtered by
     *                                                the caller. Records outside this week are never rendered.
     * @return string The rendered message.
     */
    public function makeHomeworkListOnWeek(Carbon $start, Schedule $schedule, Collection $homeworks): string
    {
        $mon = (clone $start)->startOfWeek(Carbon::MONDAY);
        $end = (clone $mon)->addDays(6);
        $homeworks = $this->mapHomeworksToDaysSubjects($homeworks);
        $params = [
            'start_date' => $mon->format('d.m'),
            'end_date' => $end->format('d.m'),
            'days' => collect(range($mon->isoWeekday(), $end->isoWeekday()))
                ->map(
                    fn (int $weekday) => $schedule->hasWorkday($weekday)
                    ? [
                        'name' => __('general.weekday.'.$weekday),
                        'lessons' => $schedule->getLessons($weekday)->map(fn (Lesson $ls) => [
                            'subject' => $ls->getSubjectName(),
                            'homework' => $this->getHomework((clone $mon)->addDays($weekday - 1), $ls->getSubjectId(), $homeworks),
                        ]),
                    ]
                        : null
                )->filter(),
        ];

        return view('messages/Homework/weekly_homework_view', $params)->render();
    }

    private function mapHomeworksToDaysSubjects(Collection $homeworks): Collection
    {
        return $homeworks->groupBy(fn (Homework $h) => $h->date->format('Y-m-d'))->sortKeys()->map(fn (Collection $h) => $h->keyBy('subject_id'));
    }

    private function getHomework(Carbon $date, int $subject_id, Collection $homeworks): string
    {
        return $homeworks->get($date->format('Y-m-d'))?->get($subject_id)?->description ?? __('info.homework.no_homework');
    }
}
