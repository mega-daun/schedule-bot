<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\EloquentHomeworkRepository;
use App\Repositories\EloquentScheduleRepository;
use App\Repositories\EloquentSubjectRepository;
use App\Repositories\HomeworkRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubjectRepository;
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
        $this->app->bind(HomeworkRepository::class, EloquentHomeworkRepository::class);
        $this->app->bind(SubjectRepository::class, EloquentSubjectRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Conversation::refreshOnDeserialize();
    }
}
