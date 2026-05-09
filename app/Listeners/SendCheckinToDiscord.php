<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Discord;
use Illuminate\Support\Facades\Log;

class SendCheckinToDiscord
{
    public function handle(CheckinCreated $event): void
    {
        try {
            Discord::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin to Discord: '.$e->getMessage());
        }
    }
}
