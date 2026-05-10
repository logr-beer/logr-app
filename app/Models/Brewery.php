<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brewery extends Model
{
    protected $fillable = [
        'name', 'city', 'state', 'country', 'latitude', 'longitude', 'website', 'logo_path',
        'catalog_beer_brewer_id',
    ];

    public function beers(): HasMany
    {
        return $this->hasMany(Beer::class);
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
                    ->orWhereNotNull('country');
            });
    }
}
