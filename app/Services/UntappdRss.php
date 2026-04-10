<?php

namespace App\Services;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UntappdRss
{
    public function syncAll(User $user): array
    {
        $feeds = $user->untappd_rss_feeds ?? [];

        if (empty($feeds)) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'No RSS feeds configured.'];
        }

        $totalImported = 0;
        $totalSkipped = 0;
        $errors = [];

        foreach ($feeds as $feed) {
            $result = $this->syncFeed($user, $feed['url']);

            $totalImported += $result['imported'];
            $totalSkipped += $result['skipped'];

            if ($result['error']) {
                $label = $feed['label'] ?: 'Unnamed feed';
                $errors[] = "{$label}: {$result['error']}";
            }
        }

        return [
            'imported' => $totalImported,
            'skipped' => $totalSkipped,
            'error' => $errors ? implode('; ', $errors) : null,
        ];
    }

    public function syncFeed(User $user, string $url): array
    {
        if (! $url) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'No RSS URL configured.'];
        }

        $response = Http::withHeaders(['User-Agent' => 'Logr/1.0'])
            ->timeout(30)
            ->get($url);

        if ($response->failed()) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'Failed to fetch RSS feed (HTTP '.$response->status().').'];
        }

        $xml = @simplexml_load_string($response->body());

        if (! $xml || ! isset($xml->channel->item)) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'Invalid RSS feed format.'];
        }

        $imported = 0;
        $skipped = 0;

        foreach ($xml->channel->item as $item) {
            $guid = (string) $item->guid;

            $parsed = $this->parseItem($item);

            if (! $parsed) {
                $skipped++;

                continue;
            }

            // Prefer the <link> URL as the Untappd ID, fall back to guid
            $untappdId = $parsed['link'] ?: $guid;

            // Skip if we've already imported this check-in
            if (Checkin::where('user_id', $user->id)->where('untappd_id', $untappdId)->exists()) {
                // Also check by guid in case older imports used it
                $skipped++;

                continue;
            }
            if ($untappdId !== $guid && Checkin::where('user_id', $user->id)->where('untappd_id', $guid)->exists()) {
                $skipped++;

                continue;
            }

            // Find or create brewery
            $brewery = null;
            if ($parsed['brewery']) {
                $brewery = Brewery::firstOrCreate(
                    ['name' => $parsed['brewery']],
                );
            }

            // Find or create beer
            $beer = Beer::firstOrCreate(
                [
                    'name' => $parsed['beer'],
                    'brewery_id' => $brewery?->id,
                ],
            );

            // Find or create venue from location
            $venueId = null;
            if ($parsed['location']) {
                $venue = Venue::firstOrCreate(['name' => $parsed['location']]);
                $venueId = $venue->id;
            }

            // Create check-in
            $checkin = Checkin::create([
                'user_id' => $user->id,
                'beer_id' => $beer->id,
                'untappd_id' => $untappdId,
                'notes' => $parsed['notes'] ?: null,
                'location' => $parsed['location'] ?: null,
                'venue_id' => $venueId,
                'created_at' => $parsed['date'],
                'updated_at' => $parsed['date'],
            ]);

            // Download and save photo if present
            if ($parsed['photo_url']) {
                try {
                    $photoResponse = Http::withHeaders(['User-Agent' => 'Logr/1.0'])
                        ->timeout(15)
                        ->get($parsed['photo_url']);

                    if ($photoResponse->successful()) {
                        $extension = 'jpg';
                        $filename = 'checkin-photos/'.$checkin->id.'_'.uniqid().'.'.$extension;
                        Storage::disk('public')->put($filename, $photoResponse->body());

                        CheckinPhoto::create([
                            'checkin_id' => $checkin->id,
                            'photo_path' => $filename,
                        ]);

                        // Use as beer photo if the beer doesn't have one
                        if (! $beer->photo_path) {
                            $beer->update(['photo_path' => $filename]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to download Untappd photo: '.$e->getMessage());
                }
            }

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => null];
    }

    protected function parseItem(\SimpleXMLElement $item): ?array
    {
        $title = (string) $item->title;
        $rawDescription = (string) $item->description;
        $pubDate = (string) $item->pubDate;
        $link = (string) $item->link;

        // Title format: "Name is drinking a Beer Name by Brewery Name"
        // Sometimes ends with: "at Location"
        if (! preg_match('/is drinking an?\s+(.+?)\s+by\s+(.+?)(?:\s+at\s+(.+))?$/i', $title, $matches)) {
            return null;
        }

        $beerName = trim($matches[1]);
        $breweryName = trim($matches[2]);
        $location = isset($matches[3]) ? trim($matches[3]) : null;

        // If location is "Untappd" or "Untappd at Home" it's not a real location
        if ($location && str_starts_with(strtolower($location), 'untappd')) {
            $location = null;
        }

        // Extract photo URL from description before stripping tags
        $photoUrl = null;
        if (preg_match('/src="(https?:\/\/[^"]+)"/', $rawDescription, $imgMatch)) {
            $photoUrl = $imgMatch[1];
        }

        // Extract notes text (strip tags and CDATA markup)
        $notes = strip_tags($rawDescription);
        $notes = trim($notes);

        return [
            'beer' => $beerName,
            'brewery' => $breweryName,
            'location' => $location,
            'notes' => $notes,
            'photo_url' => $photoUrl,
            'link' => $link ?: null,
            'date' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : now(),
        ];
    }
}
