<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTime;

trait DateHelper
{
    private function parseTextDate(string $input): ?DateTime
    {
        $input = trim($input);

        // Try YYYY-MM-DD
        $parsed = DateTime::createFromFormat('Y-m-d', $input);
        if ($parsed && $parsed->format('Y-m-d') === $input) {
            return $parsed;
        }

        // Try DD.MM.YYYY
        $parsed = DateTime::createFromFormat('d.m.Y', $input);
        if ($parsed && $parsed->format('d.m.Y') === $input) {
            return $parsed;
        }

        // Try DD.MM
        $parsed = DateTime::createFromFormat('d.m', $input);
        if ($parsed && $parsed->format('d.m') === $input) {
            return $parsed;
        }

        // Try DD
        $parsed = DateTime::createFromFormat('d', $input);
        if ($parsed && $parsed->format('d') === $input) {
            return $parsed;
        }

        return null;
    }

    private function truncateDescription(string $description, int $maxLength = 30): string
    {
        if (mb_strlen($description) <= $maxLength) {
            return $description;
        }

        return mb_substr($description, 0, $maxLength).'...';
    }
}
