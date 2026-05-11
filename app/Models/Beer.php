<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Beer extends Model
{
    protected $fillable = [
        'name', 'brewery_id', 'style', 'abv', 'ibu', 'release_year', 'brewer_master', 'description', 'photo_path', 'is_favorite',
        'catalog_beer_id', 'data',
    ];

    protected $casts = [
        'style' => 'array',
        'is_favorite' => 'boolean',
        'data' => 'array',
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

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('brewery', fn ($b) => $b->where('name', 'like', "%{$search}%"));
        });
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return str_starts_with($this->photo_path, 'http') ? $this->photo_path : Storage::url($this->photo_path);
    }

    public function averageRating(): float
    {
        return $this->checkins()->avg('rating') ?? 0;
    }
}
