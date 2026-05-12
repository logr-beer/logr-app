<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function scopeWithoutCoordinates(Builder $query): Builder
    {
        return $query->whereNull('latitude')
            ->whereRaw('LOWER(name) != ?', ['home']);
    }

    public function scopeGeocodable(Builder $query): Builder
    {
        return $query->whereNull('latitude')
            ->whereRaw('LOWER(name) != ?', ['home'])
            ->where(function ($q) {
                $q->whereNotNull('city')
                    ->orWhereNotNull('state')
                    ->orWhereNotNull('name');
            });
    }

    public function getIsHomeAttribute(): bool
    {
        return strcasecmp($this->name, 'home') === 0;
    }
}
