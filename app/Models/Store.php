<?php

namespace App\Models;

use App\Concerns\HasLocation;
use App\Policies\LocationPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(LocationPolicy::class)]
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
