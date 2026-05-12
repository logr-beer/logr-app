<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\PubDiscord;
use Illuminate\Support\Facades\Log;

class SendDiscordCheckinViaBot
{
    public function handle(CheckinCreated $event): void
    {
        if (! empty($event->shareTargets)) {
            $hasBot = collect($event->shareTargets)
                ->contains(fn ($t) => $t['type'] === 'discord_bot' && ! empty($t['enabled']));
            if (! $hasBot) {
                return;
            }
        }

        try {
            PubDiscord::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin via bot: '.$e->getMessage());
        }
    }
}
