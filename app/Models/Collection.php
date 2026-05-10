<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'cover_path', 'is_dynamic', 'rules',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'rules' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beers(): BelongsToMany
    {
        return $this->belongsToMany(Beer::class)->withPivot('sort_order')->orderByPivot('sort_order')->withTimestamps();
    }

    public function dynamicBeers(): Builder
    {
        $query = Beer::query();
        $rules = $this->rules ?? [];

        if (isset($rules['year'])) {
            $beerIds = Checkin::where('user_id', $this->user_id)
                ->whereYear('created_at', $rules['year'])
                ->distinct()
                ->pluck('beer_id');
            $query->whereIn('id', $beerIds);
        }

        if (isset($rules['style'])) {
            $query->whereJsonContains('style', $rules['style']);
        }

        if (isset($rules['min_rating'])) {
            $beerIds = Checkin::where('user_id', $this->user_id)
                ->select('beer_id')
                ->groupBy('beer_id')
                ->havingRaw('AVG(rating) >= ?', [$rules['min_rating']])
                ->pluck('beer_id');
            $query->whereIn('id', $beerIds);
        }

        if (isset($rules['favorites']) && $rules['favorites']) {
            $query->where('is_favorite', true);
        }

        if (isset($rules['oldest_in_stock']) && $rules['oldest_in_stock']) {
            $query->whereHas('inventory', fn ($q) => $q->where('quantity', '>', 0))
                ->oldest();
        }

        if (isset($rules['storage_location'])) {
            $query->whereHas('inventory', fn ($q) => $q->where('quantity', '>', 0)
                ->where('storage_location', $rules['storage_location']));
        }

        if (isset($rules['brewery'])) {
            $query->whereHas('brewery', fn ($q) => $q->where('name', 'like', '%'.$rules['brewery'].'%'));
        }

        if (isset($rules['min_abv'])) {
            $query->where('abv', '>=', $rules['min_abv']);
        }

        if (isset($rules['max_abv'])) {
            $query->where('abv', '<=', $rules['max_abv']);
        }

        if (isset($rules['serving_type'])) {
            $beerIds = Checkin::where('user_id', $this->user_id)
                ->where('serving_type', $rules['serving_type'])
                ->distinct()
                ->pluck('beer_id');
            $query->whereIn('id', $beerIds);
        }

        if (isset($rules['venue'])) {
            $beerIds = Checkin::where('user_id', $this->user_id)
                ->whereHas('venue', fn ($q) => $q->where('name', 'like', '%'.$rules['venue'].'%'))
                ->distinct()
                ->pluck('beer_id');
            $query->whereIn('id', $beerIds);
        }

        return $query;
    }

    public function resolveBeers()
    {
        if ($this->is_dynamic) {
            return $this->dynamicBeers()->with('brewery')->get();
        }

        return $this->beers()->with('brewery')->get();
    }

    public function resolveBeersCount(): int
    {
        if ($this->is_dynamic) {
            return $this->dynamicBeers()->count();
        }

        return $this->beers()->count();
    }
}
