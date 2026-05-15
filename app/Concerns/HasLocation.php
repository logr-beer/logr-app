<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocation
{
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
        return $query->whereNull('latitude');
    }

    public function scopeGeocodable(Builder $query): Builder
    {
        return $query->whereNull('latitude')
            ->where(function ($q) {
                $q->whereNotNull('city')
                    ->orWhereNotNull('state')
                    ->orWhereNotNull('name');
            });
    }
}
