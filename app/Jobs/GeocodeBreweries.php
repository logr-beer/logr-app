<?php

namespace App\Jobs;

use App\Models\Brewery;
use App\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodeBreweries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function handle(): void
    {
        try {
            $breweries = Brewery::geocodable()->get();

            $geocoded = 0;

            foreach ($breweries as $brewery) {
                $result = GeocodingService::geocode(
                    $brewery->city ?? $brewery->name,
                    $brewery->state,
                    $brewery->country
                );

                if ($result) {
                    $updates = [
                        'latitude' => $result['lat'],
                        'longitude' => $result['lng'],
                    ];
                    if (! $brewery->city && ! empty($result['city'])) {
                        $updates['city'] = $result['city'];
                    }
                    if (! $brewery->state && ! empty($result['state'])) {
                        $updates['state'] = $result['state'];
                    }
                    if (! $brewery->country && ! empty($result['country'])) {
                        $updates['country'] = $result['country'];
                    }
                    $brewery->update($updates);
                    $geocoded++;
                }

                // Nominatim rate limit: 1 request per second
                usleep(1_100_000);
            }

            Log::info("Geocoded {$geocoded} of {$breweries->count()} brewery(ies).");
        } catch (\Throwable $e) {
            Log::error('GeocodeBreweries job failed: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GeocodeBreweries job permanently failed: '.$exception->getMessage(), [
            'exception' => $exception,
        ]);
    }
}
