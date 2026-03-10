<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    public function beers(): MorphToMany
    {
        return $this->morphedByMany(Beer::class, 'taggable');
    }

    public function checkins(): MorphToMany
    {
        return $this->morphedByMany(Checkin::class, 'taggable');
    }
}
