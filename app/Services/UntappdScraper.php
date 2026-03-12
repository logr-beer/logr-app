<?php

namespace App\Services;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Venue;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UntappdScraper
{
    protected string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function scrapeUserBeers(User $user): array
    {
        $username = $user->untappd_username;

        if (! $username) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'No Untappd username configured.'];
        }

        // Use API if credentials are available (supports full pagination)
        if ($user->untappd_client_id && $user->untappd_client_secret) {
            return $this->importViaApi($user);
        }

        // Fall back to unauthenticated multi-sort scraping
        return $this->importViaHtml($user);
    }

    protected function importViaApi(User $user): array
    {
        $api = new Untappd($user->untappd_client_id, $user->untappd_client_secret);
        $imported = 0;
        $skipped = 0;
        $offset = 0;

        while (true) {
            $response = $api->getUserBeers($user->untappd_username, $offset);

            if ($response === null) {
                if ($offset === 0) {
                    return ['imported' => 0, 'skipped' => 0, 'error' => 'Failed to fetch beers from Untappd API.'];
                }
                break;
            }

            $beers = $response['beers'] ?? [];

            if (empty($beers)) {
                break;
            }

            foreach ($beers as $beerData) {
                $result = $this->importBeer($user, [
                    'untappd_bid' => $beerData['bid'],
                    'beer_name' => $beerData['name'],
                    'brewery_name' => $beerData['brewery']['name'] ?? null,
                    'style' => $beerData['style'],
                    'rating' => $beerData['rating'] ? (float) $beerData['rating'] : null,
                    'abv' => $beerData['abv'] ? (float) $beerData['abv'] : null,
                    'ibu' => $beerData['ibu'] ? (int) $beerData['ibu'] : null,
                    'label_url' => $beerData['label'],
                    'first_checkin_date' => $beerData['first_checkin_date'],
                    'recent_checkin_date' => $beerData['recent_checkin_date'],
                    'total_checkins' => $beerData['total_checkins'],
                    'checkin_url' => null,
                ]);

                if ($result) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            if (count($beers) < 50) {
                break;
            }

            $offset += 50;
            usleep(500000);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => null];
    }

    public function importViaHtml(User $user): array
    {
        $imported = 0;
        $skipped = 0;
        $seen = [];

        // Scrape multiple sort orders to maximize coverage (each returns up to 25)
        $sorts = ['date', 'alphabetical', 'highest_rated', 'lowest_rated', 'highest_abv'];

        foreach ($sorts as $sort) {
            $beers = $this->fetchPage($user->untappd_username, 0, $sort);

            if ($beers === null) {
                if (empty($seen)) {
                    return ['imported' => 0, 'skipped' => 0, 'error' => 'Failed to fetch Untappd profile.'];
                }
                continue;
            }

            foreach ($beers as $beerData) {
                // Deduplicate across sort orders
                $bid = $beerData['untappd_bid'] ?? $beerData['beer_name'];
                if (isset($seen[$bid])) {
                    continue;
                }
                $seen[$bid] = true;

                $result = $this->importBeer($user, $beerData);
                if ($result) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            usleep(300000);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => null];
    }

    protected function fetchPage(string $username, int $offset, string $sort = 'date'): ?array
    {
        if ($offset === 0) {
            $response = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->timeout(30)
                ->get("https://untappd.com/user/{$username}/beers", [
                    'sort' => $sort,
                    'start' => 0,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $this->parseHtml($response->body());
        }

        // Subsequent pages use the AJAX endpoint
        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->timeout(30)
            ->get("https://untappd.com/profile/more_beer/{$username}", [
                'sort' => 'date',
                'start' => $offset,
            ]);

        if ($response->failed()) {
            return null;
        }

        $body = $response->body();

        // AJAX endpoint may return empty string when no more beers
        if (empty(trim($body))) {
            return [];
        }

        // Try JSON response first (returns {html: "..."})
        $json = $response->json();
        if ($json && isset($json['html'])) {
            $body = $json['html'];
        }

        return $this->parseHtml($body);
    }

    protected function parseHtml(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<meta charset="utf-8">' . $html);
        $xpath = new DOMXPath($dom);

        $items = $xpath->query('//div[contains(@class, "beer-item")]');
        $beers = [];

        foreach ($items as $item) {
            $beer = $this->parseItem($xpath, $item);
            if ($beer) {
                $beers[] = $beer;
            }
        }

        return $beers;
    }

    protected function parseItem(DOMXPath $xpath, \DOMElement $item): ?array
    {
        $untappdBid = $item->getAttribute('data-bid');

        // Beer name
        $nameNode = $xpath->query('.//p[contains(@class, "name")]//a', $item)->item(0);
        $beerName = $nameNode ? trim($nameNode->textContent) : null;

        // Brewery
        $breweryNode = $xpath->query('.//p[contains(@class, "brewery")]//a', $item)->item(0);
        $breweryName = $breweryNode ? trim($breweryNode->textContent) : null;

        // Style
        $styleNode = $xpath->query('.//p[contains(@class, "style")]', $item)->item(0);
        $style = $styleNode ? trim($styleNode->textContent) : null;

        // User rating
        $ratingNode = $xpath->query('.//div[contains(@class, "ratings")]//div[contains(@class, "you")]//div[contains(@class, "caps")]', $item)->item(0);
        $rating = $ratingNode ? (float) $ratingNode->getAttribute('data-rating') : null;

        // ABV
        $abvNode = $xpath->query('.//p[contains(@class, "abv")]', $item)->item(0);
        $abv = null;
        if ($abvNode) {
            preg_match('/([\d.]+)%/', trim($abvNode->textContent), $abvMatch);
            $abv = isset($abvMatch[1]) ? (float) $abvMatch[1] : null;
        }

        // IBU
        $ibuNode = $xpath->query('.//p[contains(@class, "ibu")]', $item)->item(0);
        $ibu = null;
        if ($ibuNode) {
            preg_match('/(\d+)\s*IBU/', trim($ibuNode->textContent), $ibuMatch);
            $ibu = isset($ibuMatch[1]) ? (int) $ibuMatch[1] : null;
        }

        // Label image
        $imgNode = $xpath->query('.//a[contains(@class, "label")]//img', $item)->item(0);
        $labelUrl = $imgNode ? $imgNode->getAttribute('src') : null;

        // Check-in dates
        $dateNodes = $xpath->query('.//p[contains(@class, "date")]//abbr', $item);
        $firstCheckinDate = null;
        $recentCheckinDate = null;
        if ($dateNodes->length >= 1) {
            $firstCheckinDate = trim($dateNodes->item(0)->textContent);
        }
        if ($dateNodes->length >= 2) {
            $recentCheckinDate = trim($dateNodes->item(1)->textContent);
        }

        // Total check-ins
        $totalNode = $xpath->query('.//p[contains(@class, "check-ins")]', $item)->item(0);
        $totalCheckins = 1;
        if ($totalNode) {
            preg_match('/Total:\s*(\d+)/', trim($totalNode->textContent), $totalMatch);
            $totalCheckins = isset($totalMatch[1]) ? (int) $totalMatch[1] : 1;
        }

        // Checkin URLs for untappd_id
        $checkinLinks = $xpath->query('.//p[contains(@class, "date")]//a', $item);
        $checkinUrl = null;
        if ($checkinLinks->length > 0) {
            $checkinUrl = $checkinLinks->item(0)->getAttribute('href');
        }

        if (! $beerName) {
            return null;
        }

        return [
            'untappd_bid' => $untappdBid,
            'beer_name' => $beerName,
            'brewery_name' => $breweryName,
            'style' => $style,
            'rating' => $rating,
            'abv' => $abv,
            'ibu' => $ibu,
            'label_url' => $labelUrl,
            'first_checkin_date' => $firstCheckinDate,
            'recent_checkin_date' => $recentCheckinDate,
            'total_checkins' => $totalCheckins,
            'checkin_url' => $checkinUrl,
        ];
    }

    protected function importBeer(User $user, array $data): bool
    {
        // Find or create brewery
        $brewery = null;
        if ($data['brewery_name']) {
            $brewery = Brewery::firstOrCreate(
                ['name' => $data['brewery_name']],
            );
        }

        // Find or create beer
        $beer = Beer::where('name', $data['beer_name'])
            ->where('brewery_id', $brewery?->id)
            ->first();

        $isNew = ! $beer;

        if (! $beer) {
            $beer = Beer::create([
                'name' => $data['beer_name'],
                'brewery_id' => $brewery?->id,
                'style' => $data['style'] ? [$data['style']] : null,
                'abv' => $data['abv'],
                'ibu' => $data['ibu'],
            ]);
        } else {
            // Update missing fields on existing beer
            $updates = [];
            if (! $beer->style && $data['style']) {
                $updates['style'] = [$data['style']];
            }
            if (! $beer->abv && $data['abv']) {
                $updates['abv'] = $data['abv'];
            }
            if (! $beer->ibu && $data['ibu']) {
                $updates['ibu'] = $data['ibu'];
            }
            if (! empty($updates)) {
                $beer->update($updates);
            }
        }

        // Download label image if beer has no photo
        if (! $beer->photo_path && ! empty($data['label_url'])) {
            $this->downloadLabel($beer, $data['label_url']);
        }

        // Create a check-in if we don't already have one from this beer via scrape
        // Use the first checkin date
        if ($data['first_checkin_date']) {
            $checkinDate = date('Y-m-d H:i:s', strtotime($data['first_checkin_date']));
            $untappdId = $data['checkin_url'] ? 'https://untappd.com' . $data['checkin_url'] : null;

            // Check if we already have this checkin
            $existingCheckin = null;
            if ($untappdId) {
                $existingCheckin = Checkin::where('user_id', $user->id)->where('untappd_id', $untappdId)->first();
            }
            if (! $existingCheckin) {
                $existingCheckin = Checkin::where('user_id', $user->id)
                    ->where('beer_id', $beer->id)
                    ->where('created_at', $checkinDate)
                    ->first();
            }

            if ($existingCheckin) {
                // Backfill rating if missing
                if (! $existingCheckin->rating && $data['rating']) {
                    $existingCheckin->update(['rating' => $data['rating']]);
                }

                return false;
            }

            Checkin::create([
                'user_id' => $user->id,
                'beer_id' => $beer->id,
                'rating' => $data['rating'] ?: null,
                'untappd_id' => $untappdId,
                'created_at' => $checkinDate,
                'updated_at' => $checkinDate,
            ]);
        }

        return $isNew;
    }

    public function scrapeUserVenues(User $user): array
    {
        $username = $user->untappd_username;

        if (! $username) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'No Untappd username configured.'];
        }

        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->timeout(30)
            ->get("https://untappd.com/user/{$username}/venues");

        if ($response->failed()) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'Failed to fetch venues page (HTTP ' . $response->status() . ').'];
        }

        $dom = new DOMDocument;
        @$dom->loadHTML('<meta charset="utf-8">' . $response->body());
        $xpath = new DOMXPath($dom);

        $items = $xpath->query('//div[contains(@class, "venue-item")]');
        $imported = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $parsed = $this->parseVenueItem($xpath, $item);

            if (! $parsed || ! $parsed['name']) {
                $skipped++;

                continue;
            }

            // Find by untappd_venue_id first, then by name
            $venue = null;
            if ($parsed['untappd_venue_id']) {
                $venue = Venue::where('untappd_venue_id', $parsed['untappd_venue_id'])->first();
            }
            if (! $venue) {
                $venue = Venue::where('name', $parsed['name'])->first();
            }

            if ($venue) {
                // Backfill missing data on existing venue
                $updates = [];
                if (! $venue->untappd_venue_id && $parsed['untappd_venue_id']) {
                    $updates['untappd_venue_id'] = $parsed['untappd_venue_id'];
                }
                if (! $venue->address && $parsed['address']) {
                    $updates['address'] = $parsed['address'];
                }
                if (! $venue->city && $parsed['city']) {
                    $updates['city'] = $parsed['city'];
                }
                if (! $venue->state && $parsed['state']) {
                    $updates['state'] = $parsed['state'];
                }
                if (! empty($updates)) {
                    $venue->update($updates);
                }
                $skipped++;
            } else {
                Venue::create([
                    'name' => $parsed['name'],
                    'untappd_venue_id' => $parsed['untappd_venue_id'],
                    'address' => $parsed['address'],
                    'city' => $parsed['city'],
                    'state' => $parsed['state'],
                ]);
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => null];
    }

    protected function parseVenueItem(DOMXPath $xpath, \DOMElement $item): ?array
    {
        // Venue name from link
        $nameNode = $xpath->query('.//a[contains(@href, "/venue/")]', $item)->item(0);
        $name = $nameNode ? trim($nameNode->textContent) : null;

        // Untappd venue ID from link href
        $untappdVenueId = null;
        if ($nameNode) {
            $href = $nameNode->getAttribute('href');
            if (preg_match('/\/venue\/(\d+)/', $href, $m)) {
                $untappdVenueId = $m[1];
            }
        }

        // Address
        $addressNode = $xpath->query('.//*[contains(@class, "venue-address")]', $item)->item(0);
        $addressText = $addressNode ? trim($addressNode->textContent) : null;

        // Parse address into components (e.g. "417 Bridge St NW, Grand Rapids, MI")
        $address = null;
        $city = null;
        $state = null;

        if ($addressText) {
            $parts = array_map('trim', explode(',', $addressText));

            if (count($parts) >= 3) {
                // "417 Bridge St NW, Grand Rapids, MI"
                $address = $parts[0];
                $city = $parts[1];
                $state = $parts[2];
            } elseif (count($parts) === 2) {
                // Could be "City, ST" or "Address, City"
                if (strlen($parts[1]) <= 3) {
                    $city = $parts[0];
                    $state = $parts[1];
                } else {
                    $address = $parts[0];
                    $city = $parts[1];
                }
            } elseif (count($parts) === 1) {
                // Just a state abbreviation or city
                if (strlen($parts[0]) <= 3) {
                    $state = $parts[0];
                } else {
                    $city = $parts[0];
                }
            }
        }

        return [
            'name' => $name,
            'untappd_venue_id' => $untappdVenueId,
            'address' => $address,
            'city' => $city,
            'state' => $state,
        ];
    }

    /**
     * Search Untappd for a beer and enrich it with public data (rating, untappd_id, etc.).
     * Returns an array with keys: matched (bool), updated (bool), message (string).
     */
    public function enrichBeer(Beer $beer): array
    {
        // If we already have an untappd ID in meta, scrape directly
        $existingUntappdId = $beer->data['untappd']['id'] ?? null;
        if ($existingUntappdId) {
            $data = $this->fetchBeerPage($existingUntappdId);

            if (! $data) {
                return ['matched' => true, 'updated' => false, 'message' => "Could not fetch beer page for ID {$existingUntappdId}"];
            }

            return $this->applyBeerData($beer, $data, $existingUntappdId);
        }

        // Otherwise, search by name + brewery
        $breweryName = $beer->brewery?->name ?? '';
        $query = trim("{$beer->name} {$breweryName}");

        $untappdId = $this->searchBeerOnUntappd($query, $beer->name, $breweryName);

        if (! $untappdId) {
            return ['matched' => false, 'updated' => false, 'message' => "No Untappd match found for \"{$query}\""];
        }

        $data = $this->fetchBeerPage($untappdId);

        if (! $data) {
            return ['matched' => true, 'updated' => false, 'message' => "Matched ID {$untappdId} but could not fetch beer page"];
        }

        return $this->applyBeerData($beer, $data, $untappdId);
    }

    protected function searchBeerOnUntappd(string $query, string $beerName, string $breweryName): ?string
    {
        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->timeout(30)
            ->get('https://untappd.com/search', [
                'q' => $query,
                'type' => 'beer',
            ]);

        if ($response->failed()) {
            return null;
        }

        $html = $response->body();
        $beerNameLower = mb_strtolower(trim($beerName));
        $breweryNameLower = mb_strtolower(trim($breweryName));

        // Parse beer-item blocks for name, brewery, and /b/{slug}/{id} links
        if (preg_match_all('/class="beer-item"(.*?)(?=class="beer-item"|<\/ul>)/si', $html, $blocks)) {
            foreach ($blocks[1] as $block) {
                // Extract beer ID from /b/slug/ID
                if (! preg_match('/href="\/b\/[^"]*\/(\d+)"/', $block, $idMatch)) {
                    continue;
                }
                $beerId = $idMatch[1];

                // Beer name
                $resultName = '';
                if (preg_match('/<p[^>]*class="[^"]*name[^"]*"[^>]*>(.*?)<\/p>/si', $block, $nameMatch)) {
                    $resultName = mb_strtolower(trim(strip_tags($nameMatch[1])));
                }

                // Brewery name
                $resultBrewery = '';
                if (preg_match('/<p[^>]*class="[^"]*brewery[^"]*"[^>]*>(.*?)<\/p>/si', $block, $brewMatch)) {
                    $resultBrewery = mb_strtolower(trim(strip_tags($brewMatch[1])));
                }

                // Exact beer name + brewery contains match
                if ($resultName === $beerNameLower && ($breweryNameLower === '' || str_contains($resultBrewery, $breweryNameLower))) {
                    return $beerId;
                }

                // Beer name contains match + brewery match
                if ($breweryNameLower && str_contains($resultName, $beerNameLower) && str_contains($resultBrewery, $breweryNameLower)) {
                    return $beerId;
                }
            }
        }

        return null;
    }

    protected function fetchBeerPage(string $untappdId): ?array
    {
        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->timeout(30)
            ->get("https://untappd.com/b/beer/{$untappdId}");

        if ($response->failed()) {
            return null;
        }

        $html = $response->body();

        // Pull structured data from JSON-LD
        $data = ['rating' => null, 'url' => null, 'abv' => null, 'ibu' => null, 'description' => null, 'label_url' => null];

        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $scripts)) {
            foreach ($scripts[1] as $json) {
                $decoded = json_decode(trim($json), true);
                if ($decoded && isset($decoded['@type'])) {
                    $data['rating'] = isset($decoded['aggregateRating']['ratingValue'])
                        ? round((float) $decoded['aggregateRating']['ratingValue'], 2)
                        : null;
                    $data['url'] = $decoded['url'] ?? null;
                    $data['description'] = $decoded['description'] ?? null;
                    $data['label_url'] = $decoded['image'] ?? null;
                    break;
                }
            }
        }

        // ABV from "X% ABV"
        if (preg_match('/(\d+\.?\d*)\s*%\s*ABV/i', $html, $m)) {
            $data['abv'] = (float) $m[1];
        }

        // IBU from "X IBU" (skip "N/A")
        if (preg_match('/(\d+)\s*IBU/i', $html, $m)) {
            $data['ibu'] = (int) $m[1];
        }

        return $data;
    }

    protected function applyBeerData(Beer $beer, array $data, string $untappdId): array
    {
        // Store untappd-specific data in the data blob
        $meta = $beer->data ?? [];
        $meta['untappd'] = array_filter([
            'id' => $untappdId,
            'url' => $data['url'],
            'rating' => $data['rating'],
            'synced_at' => now()->toDateString(),
        ], fn ($v) => $v !== null);

        $updates = ['data' => $meta];

        // Backfill core fields only if currently empty
        if (! $beer->abv && $data['abv']) {
            $updates['abv'] = $data['abv'];
        }
        if (! $beer->ibu && $data['ibu']) {
            $updates['ibu'] = $data['ibu'];
        }
        if (! $beer->description && $data['description']) {
            $updates['description'] = $data['description'];
        }

        $beer->update($updates);

        // Download label if beer has no photo
        if (! $beer->photo_path && ! empty($data['label_url'])) {
            $this->downloadLabel($beer, $data['label_url']);
        }

        $rating = $data['rating'] ?? 'n/a';

        return ['matched' => true, 'updated' => true, 'message' => "Saved — Untappd ID {$untappdId}, rating {$rating}"];
    }

    protected function downloadLabel(Beer $beer, string $url): void
    {
        // Skip placeholder/default Untappd images
        if (str_contains($url, 'badge-beer-default') || str_contains($url, 'no-label')) {
            return;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->timeout(15)
                ->get($url);

            if ($response->failed()) {
                return;
            }

            $extension = 'jpg';
            $contentType = $response->header('Content-Type');
            if (str_contains($contentType, 'png')) {
                $extension = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $extension = 'webp';
            }

            $path = 'beer-photos/' . $beer->id . '_' . uniqid() . '.' . $extension;
            Storage::disk('public')->put($path, $response->body());

            $beer->update(['photo_path' => $path]);
        } catch (\Exception $e) {
            Log::debug('Failed to download beer label: ' . $e->getMessage());
        }
    }
}
