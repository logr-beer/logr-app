<?php

namespace App\Models;

use App\Concerns\HasLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use HasLocation;

    protected $fillable = [
        'name', 'address', 'city', 'state', 'country',
        'latitude', 'longitude', 'website', 'untappd_venue_id',
    ];

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function getIsHomeAttribute(): bool
    {
        return strcasecmp($this->name, 'home') === 0;
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
}
