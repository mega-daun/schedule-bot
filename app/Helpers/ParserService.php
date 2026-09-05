<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class ParserService
{
    public function parseDate(string $date): ?Carbon
    {
        $formats = ['d.m.Y', 'Y-m-d', 'd.m', 'd'];
        foreach ($formats as $format) {
            try {
                $res = Carbon::createFromFormat($format, $date);

                return $res;
            } catch (InvalidFormatException) {
                // Try next format
            }
        }

        return null;
    }
}
