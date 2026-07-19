<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MessageTextGenerator
{
    public function homeworkView(Collection $homeworks, Carbon $start, Carbon $end): string
    {
        $parts = [];
        $homeworks = $homeworks->groupBy(fn ($homework) => $homework->date->format('Y-m-d'))->sort();

        $parts[] = $this->makeHeader($start, $end);

        $dateRange = $start->toPeriod($end);
        foreach ($dateRange as $date) {
            $parts[] = $this->makeDaySubheader($date);

            if ($homeworks->has($date->format('Y-m-d'))) {
                $parts[] = $this->makeHomeworksList($homeworks[$date->format('Y-m-d')]);
            } else {
                $parts[] = $this->makeNoHomeworksMessage();
            }
        }

        return implode('', $parts);
    }

    private function makeHeader(Carbon $start, Carbon $end)
    {
        if ($start->format('Y-m-d') != $end->format('Y-m-d')) {
            return __('info.homework.view_header_range', [
                'start' => $start->format('d.m'),
                'end' => $end->format('d.m'),
            ])."\n";
        }

        return __('info.homework.view_header', [
            'start' => $start->format('d.m'),
        ])."\n";
    }

    private function makeHomeworksList(Collection $homeworks)
    {
        $list = '';
        foreach ($homeworks as $homework) {
            $list .= __('info.homework.view_item', ['description' => $homework->description])."\n";
        }

        return $list;
    }

    private function makeNoHomeworksMessage()
    {
        return __('info.homework.no_homework')."\n";
    }

    private function makeDaySubheader(Carbon $day)
    {
        return __('info.homework.view_day', [
            'weekday' => __('general.weekday.'.$day->format('N')),
            'date' => $day->format('d.m'),
        ])."\n";
    }

    public function scheduleConfirm(array $entries): string
    {
        $parts = [];
        foreach ($entries as $weekDay => $lessons) {
            $parts[] = __('weekday'.($weekDay + 1));
            foreach ($lessons as $lesson_num => $subjectName) {
                $parts[] = ($lesson_num + 1).'. '.$subjectName;
            }
            $parts[] = '';
        }

        return implode('\\n', $parts);
    }
}
