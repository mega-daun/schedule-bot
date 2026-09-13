<?php

namespace App\Helpers;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

class ParserService
{
    public function parseDate(string $date): ?Carbon
    {
        $formats = ['d.m.Y', 'Y-m-d', 'd.m', 'd'];
        foreach ($formats as $format) {
            try {
                $res = now()->createFromFormat($format, $date);

                return $res;
            } catch (InvalidFormatException) {
                // Try next format
            }
        }

        return null;
    }
}
