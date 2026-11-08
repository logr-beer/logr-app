<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

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
            $query->whereIn('id', Checkin::where('user_id', $this->user_id)
                ->whereYear('created_at', $rules['year'])
                ->select('beer_id'));
        }

        if (isset($rules['style'])) {
            $query->whereJsonContains('style', $rules['style']);
        }

        if (isset($rules['min_rating'])) {
            $query->whereIn('id', Checkin::where('user_id', $this->user_id)
                ->select('beer_id')
                ->groupBy('beer_id')
                ->havingRaw('AVG(rating) >= ?', [$rules['min_rating']]));
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
            $query->whereIn('id', Checkin::where('user_id', $this->user_id)
                ->where('serving_type', $rules['serving_type'])
                ->select('beer_id'));
        }

        if (isset($rules['venue'])) {
            $query->whereIn('id', Checkin::where('user_id', $this->user_id)
                ->whereHas('venue', fn ($q) => $q->where('name', 'like', '%'.$rules['venue'].'%'))
                ->select('beer_id'));
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

    /**
     * Count the beers of several dynamic collections in a single query.
     * With $beerId, each count is 0 or 1: whether that beer is in the collection.
     *
     * @param  iterable<Collection>  $collections
     * @return array<int, int> collection id => count
     */
    public static function dynamicBeerCounts(iterable $collections, ?int $beerId = null): array
    {
        $query = DB::query();
        $aliases = [];

        foreach ($collections as $collection) {
            $beers = $collection->dynamicBeers()->reorder();

            if ($beerId !== null) {
                $beers->where('beers.id', $beerId);
            }

            $alias = 'c'.$collection->id;
            $aliases[$collection->id] = $alias;
            $query->selectSub($beers->toBase()->selectRaw('count(*)'), $alias);
        }

        if (empty($aliases)) {
            return [];
        }

        $row = (array) $query->first();

        return array_map(fn ($alias) => (int) $row[$alias], $aliases);
    }
}
