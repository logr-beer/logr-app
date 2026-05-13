<?php

namespace App\Models;

use App\Concerns\HasLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasLocation;

    protected $fillable = [
        'name', 'address', 'city', 'state', 'country',
        'latitude', 'longitude', 'website',
    ];

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
