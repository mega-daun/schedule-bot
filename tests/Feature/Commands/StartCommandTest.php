<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

it('should reply with default message without arguments', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $updateData = [
        'update_id' => 10001,
        'message' => [
            'message_id' => 1111,
            'from' => ['id' => $user->id, 'first_name' => $user->firstName],
            'chat' => ['id' => 123456789, 'first_name' => $user->firstName],
            'text' => '/start',
        ]
    ];

    $fakeUpdate = new Update($updateData);

    Telegram::shouldReceive('getUpdates')
        ->once()
        ->andReturn(collect($fakeUpdate));

    Telegram::shouldReceive('sendMessage')
        ->once()
        ->with(Mockery::on(function))
});
