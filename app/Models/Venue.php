<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = [
        'name', 'address', 'city', 'state', 'country',
        'latitude', 'longitude', 'untappd_venue_id',
    ];

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function displayLocation(): string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);

        return implode(', ', $parts);
    }
}
