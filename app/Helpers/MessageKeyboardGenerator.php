<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class MessageKeyboardGenerator
{
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

    public function buildSelectionKeyboard(string $prefix, Collection $items, callable $generateText, callable $generateCallbackDataEntry): InlineKeyboardMarkup
    {
        $buttons = [];

        foreach ($items as $item) {
            $buttons[] = InlineKeyboardButton::make(
                text: $generateText($item),
                callback_data: "{$prefix}.{$generateCallbackDataEntry($item)}"
            );
        }

        $markup = InlineKeyboardMarkup::make();

        foreach (array_chunk($buttons, 2) as $row) {
            $markup->addRow($row);
        }

        return $markup;
    }
}
