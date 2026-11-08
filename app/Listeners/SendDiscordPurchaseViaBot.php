<?php

namespace App\Listeners;

use App\Events\PurchaseCreated;
use App\Services\PubDiscord;
use Illuminate\Support\Facades\Log;

class SendDiscordPurchaseViaBot
{
    public function handle(PurchaseCreated $event): void
    {
        try {
            PubDiscord::sendPurchase($event->inventory, $event->user, $event->shareTargets);
        } catch (\Throwable $e) {
            Log::warning('Failed to send purchase via bot: '.$e->getMessage());
        }
    }
}
