<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Hub;
use Illuminate\Support\Facades\Log;

class SendDiscordCheckinViaBot
{
    public function handle(CheckinCreated $event): void
    {
        try {
            Hub::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin via bot: '.$e->getMessage());
        }
    }
}
