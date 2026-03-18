<?php

namespace App\Providers;

use App\Events\CheckinCreated;
use App\Listeners\SendCheckinToDiscord;
use App\Listeners\SendCheckinToHub;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CheckinCreated::class, SendCheckinToDiscord::class);
        Event::listen(CheckinCreated::class, SendCheckinToHub::class);
    }
}
