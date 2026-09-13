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
            fn(int $weekdayNum) => [
                'text' => __('button_labels.keyboard.next_' . (string) $weekdayNum),
                'data' => $prefix . '.' . (clone now())->modify('+' . (7 - $todaysWeekdayNum + $weekdayNum) . ' days')->format('Y-m-d'),
            ]
        );

        if ($withCustomOption) {
            $payload->add([
                'text' => __('button_labels.keyboard.custom'),
                'data' => $prefix . '.custom',
            ]);
        }

        $keyboard = InlineKeyboardMarkup::make();
        $payload->each(
            fn(array $button) => $keyboard->addRow(new InlineKeyboardButton(
                text: $button['text'],
                callback_data: $button['data']
            ))
        );

        return $keyboard;
    }

    protected function makeFutureDatesSelectionMenu(string $prefix, bool $withCustomOption = true)
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach (['tomorrow', 'this_week', 'next_week'] as $date) {
            $keyboard->addRow(new InlineKeyboardButton(
                text: __('button_labels.keyboard.' . $date),
                callback_data: $prefix . '.' . $date,
            ));
        }

        if ($withCustomOption) {
            $keyboard->addRow(new InlineKeyboardButton(
                text: __('button_labels.keyboard.custom'),
                callback_data: $prefix . '.custom',
            ));
        }

        return $keyboard;
    }
}
