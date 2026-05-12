<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Discord;
use Illuminate\Support\Facades\Log;

class SendDiscordCheckinViaWebhook
{
    public function handle(CheckinCreated $event): void
    {
        if (! empty($event->shareTargets)) {
            $hasWebhook = collect($event->shareTargets)
                ->contains(fn ($t) => $t['type'] === 'discord_webhook' && ! empty($t['enabled']));
            if (! $hasWebhook) {
                return;
            }
        }

        try {
            Discord::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin via webhook: '.$e->getMessage());
        }
    }
}
