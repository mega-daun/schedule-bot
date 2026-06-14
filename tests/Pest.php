<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Bus;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

function bot(?App\Models\User $user = null): FakeNutgram
{
    $bot = app(Nutgram::class);

    if ($user !== null) {
        $bot->setCommonUser(User::make($user->id, false, $user->first_name, username: $user->username))
            ->setCommonChat(Chat::make($user->id, ChatType::PRIVATE));
    }

    return $bot;
}

function botWith(int $id, string $firstName, ?string $username = null, ?string $languageCode = null): FakeNutgram
{
    $bot = app(Nutgram::class);

    $bot->setCommonUser(User::make($id, false, $firstName, username: $username, language_code: $languageCode))
        ->setCommonChat(Chat::make($id, ChatType::PRIVATE));

    return $bot;
}

function botWithData(array $data, ?int $id = null, ?string $firstName = null): FakeNutgram
{
    $bot = app(Nutgram::class);

    $userId = $id ?? $data['id'] ?? 123456789;
    $userFirstName = $firstName ?? $data['first_name'] ?? 'TestUser';
    $userUsername = $data['username'] ?? 'testuser';
    $userLanguageCode = $data['language_code'] ?? 'en';

    $bot->setCommonUser(User::make($userId, false, $userFirstName, username: $userUsername, language_code: $userLanguageCode))
        ->setCommonChat(Chat::make($userId, ChatType::PRIVATE));

    foreach ($data as $key => $value) {
        $bot->set($key, $value);
    }

    return $bot;
}

function assertReplyContains(FakeNutgram $bot, string $substring, int $index = 0)
{
    $bot->assertRaw(function (Request $request) use ($substring) {
        $body = (string) $request->getBody();

        return str_contains($body, $substring);
    }, index: $index);
}

function assertReplyMarkupContains(FakeNutgram $bot, array $expectedStrings, int $index = 0)
{
    $bot->assertRaw(function (Request $request) use ($expectedStrings) {
        $body = (string) $request->getBody();

        foreach ($expectedStrings as $string) {
            if (! str_contains($body, $string)) {
                return false;
            }
        }

        return true;
    }, index: $index);
}

function assertReplyMarkupNotContains(FakeNutgram $bot, array $forbiddenStrings, int $index = 0)
{
    $bot->assertRaw(function (Request $request) use ($forbiddenStrings) {
        $body = (string) $request->getBody();

        foreach ($forbiddenStrings as $string) {
            if (str_contains($body, $string)) {
                return false;
            }
        }

        return true;
    }, index: $index);
}

uses(TestCase::class)
    ->beforeEach(function () {
        Bus::fake();
    })
    ->in('Feature');
