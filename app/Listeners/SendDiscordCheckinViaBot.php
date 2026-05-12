<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\PubDiscord;
use Illuminate\Support\Facades\Log;

class SendDiscordCheckinViaBot
{
    public function handle(CheckinCreated $event): void
    {
        try {
            PubDiscord::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin via bot: '.$e->getMessage());
        }
    }
}
