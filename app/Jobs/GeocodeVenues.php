<?php

namespace App\Jobs;

use App\Concerns\ManagesJobStatus;
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
    use Dispatchable, InteractsWithQueue, ManagesJobStatus, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    public int $maxExceptions = 3;

    protected function statusCacheKey(): string
    {
        return 'geocode_status';
    }

    public function handle(): void
    {
        try {
            Cache::put($this->statusCacheKey(), [
                'status' => 'running',
                'message' => 'Geocoding venues...',
            ], now()->addMinutes(15));

            $venues = Venue::withoutCoordinates()->get();

            $geocoded = 0;

            foreach ($venues as $venue) {
                $query = collect([$venue->address, $venue->city, $venue->state])
                    ->filter()
                    ->implode(', ');

                if (! $query) {
                    $query = $venue->name;
                }

                if (! $query) {
                    continue;
                }

                try {
                    $response = Http::withHeaders([
                        'User-Agent' => config('logr.user_agent'),
                    ])
                        ->timeout(10)
                        ->get('https://nominatim.openstreetmap.org/search', [
                            'q' => $query,
                            'format' => 'json',
                            'addressdetails' => 1,
                            'limit' => 1,
                        ]);

                    if ($response->successful()) {
                        $results = $response->json();

                        if (! empty($results)) {
                            $addr = $results[0]['address'] ?? [];
                            $updates = [
                                'latitude' => $results[0]['lat'],
                                'longitude' => $results[0]['lon'],
                            ];
                            if (! $venue->city) {
                                $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null;
                                if ($city) {
                                    $updates['city'] = $city;
                                }
                            }
                            if (! $venue->state && ! empty($addr['state'])) {
                                $updates['state'] = $addr['state'];
                            }
                            if (! $venue->country && ! empty($addr['country'])) {
                                $updates['country'] = $addr['country'];
                            }
                            $venue->update($updates);
                            $geocoded++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("Geocode failed for venue {$venue->id}: ".$e->getMessage());
                }

                // Nominatim rate limit: 1 request per second
                sleep(1);
            }

            $this->setStatusDone("Geocoded {$geocoded} of {$venues->count()} venue(s).");
        } catch (\Throwable $e) {
            Log::error('GeocodeVenues job failed: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GeocodeVenues job permanently failed: '.$exception->getMessage(), [
            'exception' => $exception,
        ]);

        $this->setStatusError('Geocoding failed: '.$exception->getMessage());
    }
}
