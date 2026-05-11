<?php

namespace App\Jobs;

use App\Models\Venue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeVenue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Venue $venue) {}

    public function handle(): void
    {
        if ($this->venue->latitude && $this->venue->longitude) {
            return;
        }

        $query = collect([$this->venue->address, $this->venue->city, $this->venue->state])
            ->filter()
            ->implode(', ');

        if (! $query) {
            $query = $this->venue->name;
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
                    'limit' => 1,
                ]);

            if ($response->successful() && ! empty($results = $response->json())) {
                $this->venue->update([
                    'latitude' => $results[0]['lat'],
                    'longitude' => $results[0]['lon'],
                ]);
            }
        } catch (\Exception $e) {
            Log::debug("Geocode failed for venue {$this->venue->id}: " . $e->getMessage());
        }
    }
}
