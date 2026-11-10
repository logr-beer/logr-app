<?php

namespace App\Events;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PurchaseCreated
{
    use Dispatchable;

    public function __construct(
        public Inventory $inventory,
        public User $user,
        public array $shareTargets = [],
    ) {}
}
