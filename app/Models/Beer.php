<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;

class Beer extends Model
{
    protected $fillable = [
        'name', 'brewery_id', 'style', 'abv', 'ibu', 'release_year', 'brewer_master', 'description', 'photo_path',
        'catalog_beer_id', 'pub_uuid', 'data',
    ];

    protected $casts = [
        'style' => 'array',
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

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'beer_user_favorites')->withTimestamps();
    }

    /**
     * Only beers the given user has favorited.
     */
    public function scopeFavoriteOf(Builder $query, ?int $userId): Builder
    {
        return $query->whereHas('favoritedBy', fn ($q) => $q->where('beer_user_favorites.user_id', $userId));
    }

    /**
     * Load the given user's favorite flag as `is_favorite` (avoids a query per beer).
     */
    public function scopeWithFavorite(Builder $query, ?int $userId): Builder
    {
        return $query->withExists(['favoritedBy as is_favorite' => fn ($q) => $q->where('beer_user_favorites.user_id', $userId)]);
    }

    /**
     * Whether the signed-in user has favorited this beer.
     */
    protected function isFavorite(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value !== null) {
                return (bool) $value;
            }

            $userId = auth()->id();

            return $userId !== null && $this->favoritedBy()->where('beer_user_favorites.user_id', $userId)->exists();
        });
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
