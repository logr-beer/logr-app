<?php

namespace App\Policies;

use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\User;
use App\Models\Venue;

class LocationPolicy
{
    /**
     * Venues, breweries and stores are a shared catalog: only allow deleting
     * one when no other user's check-ins or inventory reference it, unless
     * the user is an admin.
     */
    public function delete(User $user, Venue|Brewery|Store $location): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return match (true) {
            $location instanceof Venue => ! $location->checkins()->where('user_id', '!=', $user->id)->exists(),
            $location instanceof Store => ! $location->inventory()->where('user_id', '!=', $user->id)->exists(),
            $location instanceof Brewery => ! Checkin::whereIn('beer_id', $location->beers()->select('id'))->where('user_id', '!=', $user->id)->exists()
                && ! Inventory::whereIn('beer_id', $location->beers()->select('id'))->where('user_id', '!=', $user->id)->exists(),
        };
    }
}
