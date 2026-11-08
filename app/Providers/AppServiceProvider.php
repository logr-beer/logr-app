<?php

namespace App\Providers;

use App\Events\CheckinCreated;
use App\Http\Middleware\EnsureAdmin;
use App\Listeners\SendDiscordCheckinViaBot;
use App\Listeners\SendDiscordCheckinViaWebhook;
use App\Listeners\ShareCheckinWithPub;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Event::listen(CheckinCreated::class, SendDiscordCheckinViaWebhook::class);
        Event::listen(CheckinCreated::class, SendDiscordCheckinViaBot::class);
        Event::listen(CheckinCreated::class, ShareCheckinWithPub::class);

        // Re-check admin access on Livewire updates for components mounted on admin routes
        Livewire::addPersistentMiddleware([EnsureAdmin::class]);
    }
}
