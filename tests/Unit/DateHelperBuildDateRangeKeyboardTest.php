<?php

use App\Helpers\DateHelper;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class TestableDateHelper
{
    use DateHelper;

    public function buildDateRangeKeyboardI(string $prefix, array $additional_options = []): InlineKeyboardMarkup
    {
        return $this->buildDateRangeKeyboard($prefix, $additional_options);
    }
}

it('returns InlineKeyboardMarkup instance', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');

    expect($keyboard)->toBeInstanceOf(InlineKeyboardMarkup::class);
});

it('builds keyboard with correct callback data prefix', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('"callback_data":"showhomework.date.');
});

it('includes this week button', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('Эта неделя');
    expect($json)->toContain('showhomework.date.thisweek');
});

it('includes next week button', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('Следующая неделя');
    expect($json)->toContain('showhomework.date.nextweek');
});

it('includes custom button with "custom" data', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('Свой вариант');
    expect($json)->toContain('showhomework.date.custom');
});

it('builds keyboard with three buttons by default', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date');

    expect($keyboard->inline_keyboard)->toHaveCount(3);
});

it('adds additional options before default buttons', function () {
    $helper = new TestableDateHelper;
    $additional = ['tomorrow' => 'Завтра'];

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date', $additional);

    expect($keyboard->inline_keyboard)->toHaveCount(4);

    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    expect($json)->toContain('Завтра');
    expect($json)->toContain('showhomework.date.tomorrow');
});

it('includes tomorrow button with correct callback data when provided as additional option', function () {
    $helper = new TestableDateHelper;
    $additional = ['tomorrow' => 'Завтра'];

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date', $additional);
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('showhomework.date.tomorrow');
});

it('preserves order: additional options first, then default buttons', function () {
    $helper = new TestableDateHelper;
    $additional = ['tomorrow' => 'Завтра'];

    $keyboard = $helper->buildDateRangeKeyboardI('showhomework.date', $additional);
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    $tomorrow = now()->addDay()->format('Y-m-d');
    $positions = [
        strpos($json, 'showhomework.date.'.$tomorrow),
        strpos($json, 'showhomework.date.thisweek'),
        strpos($json, 'showhomework.date.nextweek'),
        strpos($json, 'showhomework.date.custom'),
    ];

    expect($positions[0])->toBeLessThan($positions[1]);
    expect($positions[1])->toBeLessThan($positions[2]);
    expect($positions[2])->toBeLessThan($positions[3]);
});

it('works without additional options for deletehomework scenario', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('deletehomework.date');

    expect($keyboard->inline_keyboard)->toHaveCount(3);

    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    expect($json)->toContain('Эта неделя');
    expect($json)->toContain('Следующая неделя');
    expect($json)->toContain('Свой вариант');
});

it('uses correct prefix for deletehomework', function () {
    $helper = new TestableDateHelper;

    $keyboard = $helper->buildDateRangeKeyboardI('deletehomework.date');
    $json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('"callback_data":"deletehomework.date.');
});
