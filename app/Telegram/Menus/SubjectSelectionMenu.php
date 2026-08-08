<?php

declare(strict_types=1);

namespace App\Telegram\Menus;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait SubjectSelectionMenu
{
    protected function makeSubjectSelectionMenuWithDoneButton(array $subjects, string $prefix): InlineKeyboardMarkup
    {
        $menu = InlineKeyboardMarkup::make();
        collect($subjects)->each(
            fn (array $subject) => $menu->addRow([
                new InlineKeyboardButton(
                    text: $subject['name'],
                    callback_data: $prefix.'.'.$subject['id']
                ),
            ])
        );
        $menu->addRow(new InlineKeyboardButton(
            text: __('prompt.general.done'),
            callback_data: $prefix.'.done'
        ));

        return $menu;
    }

    protected function makeSubjectSelectionMenu(array $subjects, string $prefix): InlineKeyboardMarkup
    {
        $menu = InlineKeyboardMarkup::make();
        collect($subjects)->each(
            fn (array $subject) => $menu->addRow([
                new InlineKeyboardButton(
                    text: $subject['name'],
                    callback_data: $prefix.'.'.$subject['id']
                ),
            ])
        );

        return $menu;
    }
}
