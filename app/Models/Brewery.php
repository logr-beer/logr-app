<?php

namespace App\Models;

use App\Concerns\HasLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brewery extends Model
{
    use HasLocation;

    protected $fillable = [
        'name', 'address', 'city', 'state', 'country', 'latitude', 'longitude', 'website', 'logo_path',
        'catalog_beer_brewer_id', 'pub_uuid',
    ];

    public function beers(): HasMany
    {
        return $this->hasMany(Beer::class);
    }
}
