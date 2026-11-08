<?php

namespace App\Policies;

use App\Models\Beer;
use App\Models\User;

class BeerPolicy
{
    /**
     * Beers are a shared catalog: only allow deleting one when no other
     * user has check-ins or inventory for it, unless the user is an admin.
     */
    public function delete(User $user, Beer $beer): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return ! $beer->checkins()->where('user_id', '!=', $user->id)->exists()
            && ! $beer->inventory()->where('user_id', '!=', $user->id)->exists();
    }
}
