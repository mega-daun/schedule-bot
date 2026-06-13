<?php

use App\BotCommands\Conversations\NewHomeworkConversation;

class TestableNewHomeworkConversation extends NewHomeworkConversation
{
    public function parseDate(string $data): string
    {
        return parent::parseDate($data);
    }
}

it('extracts date from callback data', function () {
    $conversation = new TestableNewHomeworkConversation;

    $result = $conversation->parseDate('newhomework.date.2026-06-15');

    expect($result)->toBe('2026-06-15');
});

it('extracts custom option from callback data', function () {
    $conversation = new TestableNewHomeworkConversation;

    $result = $conversation->parseDate('newhomework.date.custom');

    expect($result)->toBe('custom');
});

it('extracts date from single digit day', function () {
    $conversation = new TestableNewHomeworkConversation;

    $result = $conversation->parseDate('newhomework.date.2026-06-05');

    expect($result)->toBe('2026-06-05');
});
