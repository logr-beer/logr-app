<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Hub;
use Illuminate\Support\Facades\Log;

class SendCheckinToHub
{
    public function handle(CheckinCreated $event): void
    {
        try {
            Hub::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send checkin to Hub: '.$e->getMessage());
        }
    }
}
