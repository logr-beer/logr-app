<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Pub;
use Illuminate\Support\Facades\Log;

class ShareCheckinWithPub
{
    public function handle(CheckinCreated $event): void
    {
        try {
            Pub::sendCheckin($event->checkin, $event->user);
        } catch (\Throwable $e) {
            Log::warning('Failed to share checkin with Pub: ' . $e->getMessage());
        }
    }
}
