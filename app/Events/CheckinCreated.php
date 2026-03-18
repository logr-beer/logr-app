<?php

namespace App\Events;

use App\Models\Checkin;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CheckinCreated
{
    use Dispatchable;

    public function __construct(
        public Checkin $checkin,
        public User $user,
    ) {}
}
