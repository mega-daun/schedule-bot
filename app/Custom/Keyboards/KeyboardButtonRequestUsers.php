<?php

namespace App\Custom\Keyboards;

use App\Custom\ButtonTypes;

class KeyboardButtonRequestUsers
{
    /**
     * Creates valid JSON reply markup for KeyboardButtonRequestUsers keyboard
     *
     * @param  string  $text  Label for user selection
     * @param  ButtonTypes  $button_type  Id for specific selection(divided by logic)
     * @param  int  $max_quantity  Max count of users to select
     * @param  bool  $user_is_bot  Determines should selection contain bots
     */
    public static function create(string $text, ButtonTypes $button_type, int $max_quantity = 1, bool $user_is_bot = false, bool $resize_keyboard = true, bool $one_time_keyboard = true)
    {
        return json_encode([
            'keyboard' => [
                'resize_keyboard' => $resize_keyboard,
                'one_time_keyboard' => $one_time_keyboard,
                [
                    [
                        'text' => $text,
                        'request_users' => [
                            'request_id' => $button_type->value,
                            'max_quantity' => $max_quantity,
                            'user_is_bot' => $user_is_bot,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public static function createChangeUserSelection(string $text)
    {
        return static::create($text, ButtonTypes::ChangeRoleUserPolling);
    }
}
