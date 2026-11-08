<?php

namespace App\Listeners;

use App\Events\PurchaseCreated;
use App\Services\Discord;
use Illuminate\Support\Facades\Log;

class SendDiscordPurchaseViaWebhook
{
    public function handle(PurchaseCreated $event): void
    {
        try {
            Discord::sendPurchase($event->inventory, $event->user, $event->shareTargets);
        } catch (\Throwable $e) {
            Log::warning('Failed to send purchase via webhook: '.$e->getMessage());
        }
    }
}
