<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Hub;

class SendCheckinToHub
{
    public function handle(CheckinCreated $event): void
    {
        Hub::sendCheckin($event->checkin, $event->user);
    }
}
