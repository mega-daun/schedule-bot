<?php

declare(strict_types=1);

namespace App\Telegram\Menus;

use Illuminate\Support\Collection;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait HomeworkSelectionMenu
{
    protected function makeHomeworkSelectionMenu(Collection $homeworks, string $prefix): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();
        foreach ($homeworks as $homework) {
            $keyboard->addRow(
                InlineKeyboardButton::make(
                    text: $homework->date->format('d.m').' - '.$homework->description,
                    callback_data: $prefix.'.'.$homework->id
                )
            );
        }

        return $keyboard;
    }
}
