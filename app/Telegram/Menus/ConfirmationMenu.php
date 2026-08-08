<?php

declare(strict_types=1);

namespace App\Telegram\Menus;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait ConfirmationMenu
{
    protected function makeConfirmationMenu(string $prefix): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(
                new InlineKeyboardButton(
                    text: __('general.yes'),
                    callback_data: $prefix.'.yes'
                ),
                new InlineKeyboardButton(
                    text: __('general.no'),
                    callback_data: $prefix.'.no'
                )
            );
    }
}
