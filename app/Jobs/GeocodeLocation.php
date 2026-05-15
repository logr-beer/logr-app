<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Model $location) {}

    public function handle(): void
    {
        if ($this->location->latitude && $this->location->longitude) {
            return;
        }

        $query = collect([
            $this->location->address ?? null,
            $this->location->city ?? null,
            $this->location->state ?? null,
        ])->filter()->implode(', ');

        if (! $query) {
            $query = $this->location->name;
        }

        if (! $query) {
            return;
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

            if ($response->successful() && ! empty($results = $response->json())) {
                $addr = $results[0]['address'] ?? [];

                $updates = [
                    'latitude' => $results[0]['lat'],
                    'longitude' => $results[0]['lon'],
                ];

                if (! $this->location->city) {
                    $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null;
                    if ($city) {
                        $updates['city'] = $city;
                    }
                }
                if (! $this->location->state && ! empty($addr['state'])) {
                    $updates['state'] = $addr['state'];
                }
                if (! $this->location->country && ! empty($addr['country'])) {
                    $updates['country'] = $addr['country'];
                }

                $this->location->update($updates);
            }
        } catch (\Exception $e) {
            $type = class_basename($this->location);
            Log::debug("Geocode failed for {$type} {$this->location->id}: ".$e->getMessage());
        }
    }
}
