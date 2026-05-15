<?php

namespace App\Http\Controllers;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Companion;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Venue;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        abort_if(config('app.demo_mode'), 403);

        $userId = auth()->id();
        $filename = 'logr-export-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($userId) {
            echo json_encode($this->buildExport($userId), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function buildExport(int $userId): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'version' => config('logr.version'),
            'tags' => $this->exportTags(),
            'companions' => $this->exportCompanions($userId),
            'breweries' => $this->exportBreweries($userId),
            'beers' => $this->exportBeers($userId),
            'venues' => $this->exportVenues($userId),
            'stores' => $this->exportStores($userId),
            'checkins' => $this->exportCheckins($userId),
            'inventory' => $this->exportInventory($userId),
            'collections' => $this->exportCollections($userId),
        ];
    }

    private function exportTags(): array
    {
        return Tag::orderBy('name')->get()->map(fn (Tag $tag) => [
            'name' => $tag->name,
            'color' => $tag->color,
        ])->toArray();
    }

    private function exportCompanions(int $userId): array
    {
        return Companion::whereHas('checkins', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get()
            ->map(fn (Companion $c) => [
                'name' => $c->name,
            ])->toArray();
    }

    private function exportBreweries(int $userId): array
    {
        return Brewery::whereHas('beers', function ($q) use ($userId) {
            $q->where(function ($q2) use ($userId) {
                $q2->whereHas('checkins', fn ($q3) => $q3->where('user_id', $userId))
                    ->orWhereHas('inventory', fn ($q3) => $q3->where('user_id', $userId));
            });
        })
            ->orderBy('name')
            ->get()
            ->map(fn (Brewery $b) => [
                'name' => $b->name,
                'pub_uuid' => $b->pub_uuid,
                'catalog_beer_brewer_id' => $b->catalog_beer_brewer_id,
                'address' => $b->address,
                'city' => $b->city,
                'state' => $b->state,
                'country' => $b->country,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'website' => $b->website,
            ])->toArray();
    }

    private function exportBeers(int $userId): array
    {
        return Beer::with(['brewery', 'tags'])
            ->where(function ($q) use ($userId) {
                $q->whereHas('checkins', fn ($q2) => $q2->where('user_id', $userId))
                    ->orWhereHas('inventory', fn ($q2) => $q2->where('user_id', $userId));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Beer $beer) => [
                'name' => $beer->name,
                'pub_uuid' => $beer->pub_uuid,
                'catalog_beer_id' => $beer->catalog_beer_id,
                'brewery_name' => $beer->brewery->name ?? null,
                'brewery_pub_uuid' => $beer->brewery->pub_uuid ?? null,
                'style' => $beer->style,
                'abv' => $beer->abv,
                'ibu' => $beer->ibu,
                'release_year' => $beer->release_year,
                'brewer_master' => $beer->brewer_master,
                'description' => $beer->description,
                'is_favorite' => $beer->is_favorite,
                'tags' => $beer->tags->pluck('name')->toArray(),
            ])->toArray();
    }

    private function exportVenues(int $userId): array
    {
        return Venue::whereHas('checkins', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get()
            ->map(fn (Venue $v) => [
                'name' => $v->name,
                'untappd_venue_id' => $v->untappd_venue_id,
                'address' => $v->address,
                'city' => $v->city,
                'state' => $v->state,
                'country' => $v->country,
                'latitude' => $v->latitude,
                'longitude' => $v->longitude,
                'website' => $v->website,
            ])->toArray();
    }

    private function exportStores(int $userId): array
    {
        return Store::whereHas('inventory', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->get()
            ->map(fn (Store $s) => [
                'name' => $s->name,
                'address' => $s->address,
                'city' => $s->city,
                'state' => $s->state,
                'country' => $s->country,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
                'website' => $s->website,
            ])->toArray();
    }

    private function exportCheckins(int $userId): array
    {
        $checkins = [];

        Checkin::where('user_id', $userId)
            ->with(['beer.brewery', 'venue', 'photos', 'tags', 'companions'])
            ->latest()
            ->chunk(200, function ($chunk) use (&$checkins) {
                foreach ($chunk as $checkin) {
                    $checkins[] = [
                        'beer_name' => $checkin->beer->name ?? null,
                        'beer_pub_uuid' => $checkin->beer->pub_uuid ?? null,
                        'brewery_name' => $checkin->beer->brewery->name ?? null,
                        'brewery_pub_uuid' => $checkin->beer->brewery->pub_uuid ?? null,
                        'rating' => $checkin->rating,
                        'serving_type' => $checkin->serving_type,
                        'notes' => $checkin->notes,
                        'location' => $checkin->location,
                        'venue_name' => $checkin->venue->name ?? null,
                        'untappd_id' => $checkin->untappd_id,
                        'tags' => $checkin->tags->pluck('name')->toArray(),
                        'companions' => $checkin->companions->pluck('name')->toArray(),
                        'created_at' => $checkin->created_at->toIso8601String(),
                    ];
                }
            });

        return $checkins;
    }

    private function exportInventory(int $userId): array
    {
        return Inventory::where('user_id', $userId)
            ->with(['beer.brewery', 'store'])
            ->get()
            ->map(fn (Inventory $inv) => [
                'beer_name' => $inv->beer->name ?? null,
                'beer_pub_uuid' => $inv->beer->pub_uuid ?? null,
                'brewery_name' => $inv->beer->brewery->name ?? null,
                'brewery_pub_uuid' => $inv->beer->brewery->pub_uuid ?? null,
                'quantity' => $inv->quantity,
                'storage_location' => $inv->storage_location,
                'store_name' => $inv->store->name ?? null,
                'is_gift' => $inv->is_gift,
                'date_acquired' => $inv->date_acquired?->toDateString(),
                'notes' => $inv->notes,
            ])->toArray();
    }

    private function exportCollections(int $userId): array
    {
        return Collection::where('user_id', $userId)
            ->with('beers.brewery')
            ->orderBy('name')
            ->get()
            ->map(fn (Collection $c) => [
                'name' => $c->name,
                'description' => $c->description,
                'is_dynamic' => $c->is_dynamic,
                'rules' => $c->rules,
                'beers' => $c->is_dynamic ? [] : $c->beers->map(fn (Beer $b) => [
                    'name' => $b->name,
                    'pub_uuid' => $b->pub_uuid,
                    'brewery_name' => $b->brewery->name ?? null,
                    'sort_order' => $b->pivot->sort_order,
                ])->toArray(),
            ])->toArray();
    }
}
