<?php

namespace App\Jobs;

use App\Models\Venue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeVenues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(): void
    {
        Cache::put('geocode_status', [
            'status' => 'running',
            'message' => 'Geocoding venues...',
        ], now()->addMinutes(15));

        $venues = Venue::whereNull('latitude')->get();

        $geocoded = 0;

        foreach ($venues as $venue) {
            // Build query from address components, fall back to venue name
            $query = collect([$venue->address, $venue->city, $venue->state])
                ->filter()
                ->implode(', ');

            if (! $query) {
                // Fall back to venue name for name-only venues
                $query = $venue->name;
            }

            if (! $query) {
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Logr/1.0 (personal beer tracker)',
                ])
                    ->timeout(10)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'json',
                        'limit' => 1,
                    ]);

                if ($response->successful()) {
                    $results = $response->json();

                    if (! empty($results)) {
                        $venue->update([
                            'latitude' => $results[0]['lat'],
                            'longitude' => $results[0]['lon'],
                        ]);
                        $geocoded++;
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Geocode failed for venue {$venue->id}: " . $e->getMessage());
            }

            // Nominatim rate limit: 1 request per second
            sleep(1);
        }

        Cache::put('geocode_status', [
            'status' => 'done',
            'message' => "Geocoded {$geocoded} of {$venues->count()} venue(s).",
        ], now()->addMinutes(10));
    }
}
