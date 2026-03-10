<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Beer extends Model
{
    protected $fillable = [
        'name', 'brewery_id', 'style', 'abv', 'ibu', 'release_year', 'brewer_master', 'description', 'photo_path', 'is_favorite',
    ];

    protected $casts = [
        'style' => 'array',
        'is_favorite' => 'boolean',
    ];

    public function brewery(): BelongsTo
    {
        return $this->belongsTo(Brewery::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort_order')->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function averageRating(): float
    {
        return $this->checkins()->avg('rating') ?? 0;
    }
}
