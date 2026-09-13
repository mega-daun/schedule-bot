<?php

namespace App\Telegram\Menus;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait DateSelectionMenu
{
    protected function makeFutureWeekdaySelectionMenu(array $allowedWeekdayNums, string $prefix, bool $withCustomOption = true): InlineKeyboardMarkup
    {
        $payload = collect($allowedWeekdayNums);
        $todaysWeekdayNum = now()->isoWeekday();

        $payload = $payload->map(
            fn (int $weekdayNum) => [
                'text' => __('button_labels.keyboard.next_'.(string) $weekdayNum),
                'data' => $prefix.'.'.(clone now())->modify('+'.(7 - $todaysWeekdayNum + $weekdayNum).' days')->format('Y-m-d'),
            ]
        );

        if ($withCustomOption) {
            $payload->add([
                'text' => __('button_labels.keyboard.custom'),
                'data' => $prefix.'.custom',
            ]);
        }

        $keyboard = InlineKeyboardMarkup::make();
        $payload->each(
            fn (array $button) => $keyboard->addRow(new InlineKeyboardButton(
                text: $button['text'],
                callback_data: $button['data']
            ))
        );

        return $keyboard;
    }

    /**
     * Build an inline keyboard from an array of pre-formatted options.
     *
     * Each option must be an array with 'text' and 'data' keys. The 'data'
     * value must be the full callback data including the prefix.
     *
     * @param  array<int, array{text: string, data: string}>  $options
     */
    protected function makeOptionSelectionMenu(array $options): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($options as $option) {
            $keyboard->addRow(new InlineKeyboardButton(
                text: $option['text'],
                callback_data: $option['data'],
            ));
        }

        return $keyboard;
    }
}
