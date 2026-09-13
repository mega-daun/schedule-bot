<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class MessageKeyboardGenerator
{
    public function buildSelectionKeyboard(string $prefix, Collection $items, callable $generateText, callable $generateCallbackDataEntry, int $buttons_per_row = 2, array $additional_options = []): InlineKeyboardMarkup
    {
        $buttons = [];

        foreach ($items as $item) {
            $buttons[] = InlineKeyboardButton::make(
                text: $generateText($item),
                callback_data: "{$prefix}.{$generateCallbackDataEntry($item)}"
            );
        }

        foreach ($additional_options as $opt_data => $opt_name) {
            $buttons[] = InlineKeyboardButton::make(
                text: $opt_name,
                callback_data: $opt_data
            );
        }

        $markup = InlineKeyboardMarkup::make();

        foreach (array_chunk($buttons, $buttons_per_row) as $row) {
            $markup->addRow(...$row);
        }

        return $markup;
    }
}
