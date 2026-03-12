<?php

namespace App\Console\Commands;

use App\Models\Brewery;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeBreweries extends Command
{
    protected $signature = 'breweries:geocode {--force : Re-geocode breweries that already have coordinates}';

    protected $description = 'Geocode breweries that are missing latitude/longitude using city/state/country';

    public function handle(): int
    {
        $query = Brewery::query()
            ->where(function ($q) {
                $q->whereNotNull('city')
                    ->orWhereNotNull('state')
                    ->orWhereNotNull('country');
            });

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $breweries = $query->get();

        if ($breweries->isEmpty()) {
            $this->info('No breweries to geocode.');
            return self::SUCCESS;
        }

        $this->info("Geocoding {$breweries->count()} breweries...");
        $bar = $this->output->createProgressBar($breweries->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($breweries as $brewery) {
            $coords = GeocodingService::geocode($brewery->city, $brewery->state, $brewery->country);

            if ($coords) {
                $brewery->update(['latitude' => $coords['lat'], 'longitude' => $coords['lng']]);
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();

            // Respect Nominatim rate limit: 1 request per second
            usleep(1_100_000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Geocoded: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
