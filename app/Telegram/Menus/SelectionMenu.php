<?php

declare(strict_types=1);

namespace App\Telegram\Menus;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait SelectionMenu
{
    /**
     * Build an inline keyboard from an array of pre-formatted options.
     *
     * Each option must be an array with 'text' and 'data' keys. The 'data'
     * value must be the full callback data including the prefix.
     *
     * @param  array<int, string>  $options
     */
    protected function makeOptionSelectionMenu(array $options, string $prefix): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($options as $option) {
            $keyboard->addRow(new InlineKeyboardButton(
                text: __('button_labels.keyboard.'.$option),
                callback_data: $prefix.'.'.$option,
            ));
        }

        return $keyboard;
    }
}
