<?php

namespace App\Listeners;

use App\Events\CheckinCreated;
use App\Services\Discord;

class SendCheckinToDiscord
{
    public function handle(CheckinCreated $event): void
    {
        Discord::sendCheckin($event->checkin, $event->user);
    }
}
