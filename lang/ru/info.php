<?php

return [
    'class' => [
        'created' => 'Класс :code успешно создан. Токен для присоединения: :token. Ссылка для присоединения: https://t.me/'.env('TELEGRAM_BOT_USERNAME').'?start=:token',
        'joined' => 'Вы успешно присоеденились к классу :code.',
        'left' => 'Вы вышли из класса :code.',
        'deleted' => 'Вы успешно удалили класс :code.',
        'deleted_broadcast' => 'Класс, в котором вы состояли, был удалён.',
    ],
    'role' => [
        'changed' => 'Роль изменена',
        'changed_to' => 'Роль изменена на :role',
    ],
    'homework' => [
        'created' => 'Домашнее задание успешно создано',
        'deleted' => 'Домашнее задание успешно удалено',
        'view_header' => '❗️ДЗ на :start',
        'view_header_range' => '❗️ДЗ на :start-:end',
        'no_homework' => '▫️ Ничего не задали :)',
        'view_item' => '▫️ :description',
        'view_day' => '⬜️ :weekday(:date)',
    ],
    'subject' => [
        'created' => 'Предмет :name успешно создан',
        'deleted' => 'Предмет успешно удалён',
    ],
    'cancel' => [
        'done' => 'Действие отменено.',
    ],
    'schedule' => [
        'created' => 'Расписание класса успешно создано.',
        'recreating' => 'Пересоздаём расписание.',
    ],
];
