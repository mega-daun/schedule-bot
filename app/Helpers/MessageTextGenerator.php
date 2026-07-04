<?php

namespace App\Helpers;

use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Collection;

class MessageTextGenerator
{
    public function homeworkView(Collection $homeworks, DateTime $start, DateTime $end): string {
        $parts = [];
        $homeworks = $homeworks->groupBy(fn ($homework) => $homework->date->format('Y-m-d'))->sort();

        $parts[] = $this->makeHeader($start, $end);

        $dateRange = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
        foreach ($dateRange as $date) {
            $parts[] = $this->makeDaySubheader($date);

            if ($homeworks->has($date->format('Y-m-d'))) {
                $parts[] = $this->makeHomeworksList($homeworks[$date->format('Y-m-d')]);
            }
            else {
                $parts[] = $this->makeNoHomeworksMessage();
            }
        }
        return implode('', $parts);
    }

    private function makeHeader(DateTime $start, DateTime $end)
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

    private function makeDaySubheader(DateTime $day)
    {
        return __('info.homework.view_day', [
            'weekday' => __('general.weekday.'.$day->format('N')),
            'date' => $day->format('d.m'),
        ])."\n";
    }
}
