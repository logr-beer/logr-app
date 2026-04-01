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

    public function handle(): void
    {
        $breweries = Brewery::whereNull('latitude')
            ->where(function ($q) {
                $q->whereNotNull('city')
                    ->orWhereNotNull('state')
                    ->orWhereNotNull('country');
            })
            ->get();

        $geocoded = 0;

        foreach ($breweries as $brewery) {
            $result = GeocodingService::geocode($brewery->city, $brewery->state, $brewery->country);

            if ($result) {
                $brewery->update([
                    'latitude' => $result['lat'],
                    'longitude' => $result['lng'],
                ]);
                $geocoded++;
            }

            // Nominatim rate limit: 1 request per second
            usleep(1_100_000);
        }

        Log::info("Geocoded {$geocoded} of {$breweries->count()} brewery(ies).");
    }
}
