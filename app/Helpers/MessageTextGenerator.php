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
        $header = '';
        $header .= "❗️ДЗ на " . $start->format('d.m');
        if ($start->format('Y-m-d') != $end->format('Y-m-d')) {
            $header .= "-" . $end->format('d.m');
        }
        $header .= "\n";
        return $header;
    }

    private function makeHomeworksList(Collection $homeworks)
    {
        $list = '';
        foreach ($homeworks as $homework) {
            $list .= '▫️ ' . $homework->description . "\n";
        }
        return $list;
    }

    private function makeNoHomeworksMessage()
    {
        return '▫️ Ничего не задали :)' . "\n";
    }

    private function makeDaySubheader(DateTime $day)
    {
        return '⬜️ ' . self::WEEKDAY_NUMBER_TO_NAME[$day->format('N')] . '(' . $day->format('d.m') . ')' . "\n";
    }

    private const WEEKDAY_NUMBER_TO_NAME = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];
}
