<?php

namespace App\Livewire;

use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class VenueShow extends Component
{
    public Venue $venue;

    public bool $editing = false;
    public string $name = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $country = '';
    public ?string $latitude = null;
    public ?string $longitude = null;

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
        $this->fillForm();
    }

    public function fillForm(): void
    {
        $this->name = $this->venue->name ?? '';
        $this->address = $this->venue->address ?? '';
        $this->city = $this->venue->city ?? '';
        $this->state = $this->venue->state ?? '';
        $this->country = $this->venue->country ?? '';
        $this->latitude = $this->venue->latitude;
        $this->longitude = $this->venue->longitude;
    }

    public function edit(): void
    {
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->fillForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $this->venue->update([
            'name' => $this->name,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country ?: null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
        ]);

        $this->venue->refresh();
        $this->editing = false;
    }

    public string $geocodeError = '';

    public function lookupCoordinates(): void
    {
        $this->geocodeError = '';

        // Build a list of queries to try, from most specific to least
        $queries = [];

        // 1. Full structured search
        $structured = array_filter([
            'street' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
        ]);
        if (! empty($structured)) {
            $queries[] = $structured;
        }

        // 2. Free-form with all fields
        $allParts = collect([$this->address, $this->city, $this->state, $this->country])->filter()->implode(', ');
        if ($allParts) {
            $queries[] = ['q' => $allParts];
        }

        // 3. City + state + country only (skip street)
        $cityState = collect([$this->city, $this->state, $this->country])->filter()->implode(', ');
        if ($cityState && $cityState !== $allParts) {
            $queries[] = ['q' => $cityState];
        }

        // 4. Venue name as last resort
        if ($this->name) {
            $queries[] = ['q' => $this->name];
        }

        if (empty($queries)) {
            $this->geocodeError = 'Enter an address or name first.';
            return;
        }

        $usedFallback = false;

        try {
            foreach ($queries as $i => $params) {
                $params['format'] = 'json';
                $params['limit'] = 1;

                $response = Http::withHeaders([
                    'User-Agent' => 'Logr/1.0 (personal beer tracker)',
                ])
                    ->timeout(10)
                    ->get('https://nominatim.openstreetmap.org/search', $params);

                $results = $response->successful() ? $response->json() : [];

                if (! empty($results)) {
                    $this->latitude = $results[0]['lat'];
                    $this->longitude = $results[0]['lon'];

                    // Let the user know if we used a less specific match
                    if ($i >= 2) {
                        $matched = $results[0]['display_name'] ?? '';
                        $this->geocodeError = "Exact address not found. Matched: {$matched}";
                    }

                    return;
                }

                // Respect Nominatim rate limit between retries
                if ($i < count($queries) - 1) {
                    usleep(500000);
                }
            }

            $this->geocodeError = 'No results found. Try adjusting the address or enter coordinates manually.';
        } catch (\Exception $e) {
            $this->geocodeError = 'Geocoding failed: ' . $e->getMessage();
        }
    }

    public function delete(): void
    {
        if ($this->venue->checkins()->where('user_id', auth()->id())->doesntExist()) {
            abort(403);
        }

        $this->venue->delete();

        $this->redirect(route('locations.venues'), navigate: true);
    }

    public function render()
    {
        $checkins = $this->venue->checkins()
            ->with(['beer.brewery', 'photos'])
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.venue-show', [
            'checkins' => $checkins,
        ])->title($this->venue->name . ' | Venues');
    }
}
