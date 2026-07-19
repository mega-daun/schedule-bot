<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\EloquentScheduleRepository;
use App\Repositories\ScheduleRepository;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Conversations\Conversation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ScheduleRepository::class, EloquentScheduleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Conversation::refreshOnDeserialize(true);
    }
}
