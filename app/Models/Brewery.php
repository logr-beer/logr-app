<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brewery extends Model
{
    protected $fillable = [
        'name', 'city', 'state', 'country', 'website', 'logo_path',
    ];

    public function beers(): HasMany
    {
        return $this->hasMany(Beer::class);
    }
}
