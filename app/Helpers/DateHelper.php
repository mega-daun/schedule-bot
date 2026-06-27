<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTime;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

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

    private function buildDateRangeKeyboard(string $prefix, array $additional_options = []): InlineKeyboardMarkup
    {
        $mkup = InlineKeyboardMarkup::make();

        foreach ($additional_options as $option_data => $option_name) {
            $mkup->addRow([
                InlineKeyboardButton::make(
                    text: $option_name,
                    callback_data: $prefix.'.'.$option_data,
                ),
            ]);
        }

        $mkup->addRow([
            InlineKeyboardButton::make(
                text: 'Эта неделя',
                callback_data: $prefix.'.thisweek'
            ),
        ])
            ->addRow([
                InlineKeyboardButton::make(
                    text: 'Следующая неделя',
                    callback_data: $prefix.'.nextweek'
                ),
            ])
            ->addRow([
                InlineKeyboardButton::make(
                    text: 'Свой вариант',
                    callback_data: $prefix.'.custom'
                ),
            ]);

        return $mkup;
    }
}
