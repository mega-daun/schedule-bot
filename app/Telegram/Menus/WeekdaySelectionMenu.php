<?php

declare(strict_types=1);

namespace App\Telegram\Menus;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait WeekdaySelectionMenu
{
    protected function makeMultipleWeekdaySelectionMenu(array $selectedWorkdays, string $prefix): InlineKeyboardMarkup
    {
        $payload = collect(range(1, 7))->map(
            fn (int $weekday) => in_array($weekday, $selectedWorkdays)
            ? ['label' => __('prompt.general.marked', ['item' => __('general.weekday.'.$weekday)]), 'data' => $prefix.'.remove.'.$weekday]
            : ['label' => __('general.weekday.'.$weekday), 'data' => $prefix.'.add.'.$weekday]
        );
        $payload->add(['label' => __('prompt.general.done'), 'data' => 'done.done']);
        $payload = $payload->map(
            fn (array $item) => new InlineKeyboardButton(
                text: $item['label'],
                callback_data: $item['data']
            )
        );
        $menu = InlineKeyboardMarkup::make();
        $payload->each(fn (InlineKeyboardButton $btn) => $menu->addRow([$btn]));

        return $menu;
    }

    protected function makeWeekdaySelectionMenu(string $prefix, array $weekdays = [1, 2, 3, 4, 5, 6, 7]): InlineKeyboardMarkup
    {
        $payload = collect($weekdays)->map(
            fn (int $weekday) => ['label' => __('general.weekday.'.$weekday), 'data' => $prefix.'.add.'.$weekday]
        );
        $payload = $payload->map(
            fn (array $item) => new InlineKeyboardButton(
                text: $item['label'],
                callback_data: $item['data']
            )
        );
        $menu = InlineKeyboardMarkup::make();
        $payload->each(fn (InlineKeyboardButton $btn) => $menu->addRow([$btn]));

        return $menu;
    }
}
