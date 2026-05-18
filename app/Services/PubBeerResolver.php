<?php

namespace App\Services;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PubBeerResolver
{
    private PubBeerDb $pub;

    private ?User $user;

    public function __construct(PubBeerDb $pub, ?User $user = null)
    {
        $this->pub = $pub;
        $this->user = $user;
    }

    public static function make(?User $user = null): ?self
    {
        $pub = PubBeerDb::forInstance();
        if (! $pub) {
            return null;
        }

        return new self($pub, $user);
    }

    public function resolveBrewery(string $breweryName): Brewery
    {
        $existing = Brewery::where('name', $breweryName)->first();
        if ($existing && $existing->pub_uuid) {
            return $existing;
        }

        $results = $this->pub->searchBreweries($breweryName, 3);
        $match = $this->bestBreweryMatch($results, $breweryName);

        if ($match) {
            $brewery = Brewery::firstOrCreate(['name' => $breweryName]);
            $this->backfillBrewery($brewery, $match);

            return $brewery;
        }

        return Brewery::firstOrCreate(['name' => $breweryName]);
    }

    public function resolveBeer(string $beerName, ?Brewery $brewery, array $localData = []): Beer
    {
        $existing = Beer::where('name', $beerName)
            ->where('brewery_id', $brewery?->id)
            ->first();

        if ($existing && $existing->pub_uuid) {
            return $existing;
        }

        $query = trim("{$beerName} ".($brewery?->name ?? ''));
        $searchResults = $this->pub->search($query);
        $match = $this->bestBeerMatch($searchResults['beers'] ?? [], $beerName, $brewery?->name);

        if ($match) {
            $beer = Beer::firstOrCreate(
                ['name' => $beerName, 'brewery_id' => $brewery?->id]
            );
            $this->backfillBeer($beer, $match);

            // Backfill brewery pub_uuid from the beer result
            if ($brewery && ! $brewery->pub_uuid && ($match['brewery_id'] ?? null)) {
                $brewery->update(['pub_uuid' => $match['brewery_id']]);
            }

            return $beer;
        }

        // No beer match — but check if breweries came back from the search
        if ($brewery && ! $brewery->pub_uuid && ! empty($searchResults['breweries'])) {
            $breweryMatch = $this->bestBreweryMatch($searchResults['breweries'], $brewery->name);
            if ($breweryMatch) {
                $this->backfillBrewery($brewery, $breweryMatch);
            }
        }

        // Create locally
        $beer = Beer::firstOrCreate(
            ['name' => $beerName, 'brewery_id' => $brewery?->id],
            array_filter([
                'style' => isset($localData['style']) ? (array) $localData['style'] : null,
                'abv' => $localData['abv'] ?? null,
                'ibu' => $localData['ibu'] ?? null,
            ], fn ($v) => $v !== null)
        );

        return $beer;
    }

    public function submitUnmatched(Beer $beer, ?Brewery $brewery, array $localData = []): void
    {
        if (! $this->user) {
            return;
        }

        $secretKey = $this->user->getData('pub_secret_key');
        if (! $secretKey) {
            return;
        }

        $payload = [
            'name' => $beer->name,
            'brewery_name' => $brewery?->name,
            'brewery_id' => $brewery?->pub_uuid,
            'abv' => $beer->abv ?? ($localData['abv'] ?? null),
            'style' => $beer->style ?? (isset($localData['style']) ? (array) $localData['style'] : null),
            'description' => $beer->description ?? ($localData['description'] ?? null),
        ];

        $result = $this->pub->submitBeer($secretKey, $payload);

        if ($result && ($result['status'] ?? null) === 401) {
            PubBeerDb::handleSecretKeyRevoked($this->user->id);
            Log::info('PubBeerResolver: secret key revoked during submission', ['user_id' => $this->user->id]);
        }
    }

    private function bestBreweryMatch(array $results, string $name): ?array
    {
        $nameLower = mb_strtolower(trim($name));
        foreach ($results as $r) {
            if (mb_strtolower(trim($r['name'])) === $nameLower) {
                return $r;
            }
        }

        return null;
    }

    private function bestBeerMatch(array $results, string $beerName, ?string $breweryName): ?array
    {
        $beerLower = mb_strtolower(trim($beerName));
        $breweryLower = $breweryName ? mb_strtolower(trim($breweryName)) : null;

        foreach ($results as $r) {
            $nameMatch = mb_strtolower(trim($r['name'] ?? '')) === $beerLower;
            $breweryMatch = ! $breweryLower
                || mb_strtolower(trim($r['brewery_name'] ?? $r['brewery']['name'] ?? '')) === $breweryLower;

            if ($nameMatch && $breweryMatch) {
                return $r;
            }
        }

        return null;
    }

    private function backfillBrewery(Brewery $brewery, array $match): void
    {
        $updates = [];
        if (! $brewery->pub_uuid && ($match['id'] ?? null)) {
            $updates['pub_uuid'] = $match['id'];
        }
        if (! $brewery->city && ($match['city'] ?? null)) {
            $updates['city'] = $match['city'];
        }
        if (! $brewery->state && ($match['state'] ?? null)) {
            $updates['state'] = $match['state'];
        }
        if (! $brewery->country && ($match['country'] ?? null)) {
            $updates['country'] = $match['country'];
        }
        if (! $brewery->website && ($match['website'] ?? null)) {
            $updates['website'] = $match['website'];
        }

        if (! empty($updates)) {
            $brewery->update($updates);
        }
    }

    private function backfillBeer(Beer $beer, array $match): void
    {
        $updates = [];
        if (! $beer->pub_uuid && ($match['id'] ?? null)) {
            $updates['pub_uuid'] = $match['id'];
        }
        $matchStyle = $match['styles'] ?? $match['style'] ?? null;
        if (! $beer->style && $matchStyle) {
            $updates['style'] = is_array($matchStyle) ? $matchStyle : [$matchStyle];
        }
        if (! $beer->abv && ($match['abv'] ?? null)) {
            $updates['abv'] = $match['abv'];
        }
        if (! $beer->ibu && ($match['ibu'] ?? null)) {
            $updates['ibu'] = $match['ibu'];
        }
        if (! $beer->description && ($match['description'] ?? null)) {
            $updates['description'] = $match['description'];
        }

        if (! empty($updates)) {
            $beer->update($updates);
        }
    }
}
