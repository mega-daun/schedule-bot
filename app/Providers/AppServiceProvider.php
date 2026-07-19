<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\EloquentWeeklyScheduleEntryRepository;
use App\Repositories\WeeklyScheduleEntryRepository;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Conversations\Conversation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WeeklyScheduleEntryRepository::class, EloquentWeeklyScheduleEntryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Conversation::refreshOnDeserialize(true);
    }
}
