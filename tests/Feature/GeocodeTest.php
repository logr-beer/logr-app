<?php

namespace Tests\Feature;

use App\Jobs\GeocodeLocation;
use App\Models\Brewery;
use App\Models\Store;
use App\Models\Venue;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodeTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNominatimResponse(array $overrides = []): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                array_merge([
                    'lat' => '44.9778',
                    'lon' => '-93.2650',
                    'address' => [
                        'city' => 'Minneapolis',
                        'state' => 'Minnesota',
                        'country' => 'United States',
                        'road' => 'Hennepin Avenue',
                        'house_number' => '123',
                    ],
                ], $overrides),
            ]),
        ]);
    }

    // ─── GeocodeLocation Job ─────────────────────────────────────────

    public function test_geocode_location_skips_when_coordinates_exist(): void
    {
        Http::fake();

        $store = Store::create([
            'name' => 'Test Store',
            'latitude' => 44.0,
            'longitude' => -93.0,
        ]);

        GeocodeLocation::dispatchSync($store);

        Http::assertNothingSent();
    }

    public function test_geocode_location_skips_when_no_query_data(): void
    {
        Http::fake();

        $store = Store::create([
            'name' => '',
        ]);

        GeocodeLocation::dispatchSync($store);

        Http::assertNothingSent();
    }

    public function test_geocode_location_calls_nominatim_with_address_city_state(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'address' => '123 Main St',
            'city' => 'Minneapolis',
            'state' => 'Minnesota',
        ]);

        GeocodeLocation::dispatchSync($store);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org')
                && $request['q'] === '123 Main St, Minneapolis, Minnesota';
        });
    }

    public function test_geocode_location_falls_back_to_name(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Surly Brewing',
        ]);

        GeocodeLocation::dispatchSync($store);

        Http::assertSent(function ($request) {
            return $request['q'] === 'Surly Brewing';
        });
    }

    public function test_geocode_location_updates_lat_lng(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('44.9778', $store->latitude);
        $this->assertEquals('-93.2650', $store->longitude);
    }

    public function test_geocode_location_backfills_city_when_missing(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'state' => 'Minnesota',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('Minneapolis', $store->city);
    }

    public function test_geocode_location_backfills_state_when_missing(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('Minnesota', $store->state);
    }

    public function test_geocode_location_backfills_country_when_missing(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('United States', $store->country);
    }

    public function test_geocode_location_does_not_overwrite_existing_city_state_country(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Saint Paul',
            'state' => 'MN',
            'country' => 'US',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('Saint Paul', $store->city);
        $this->assertEquals('MN', $store->state);
        $this->assertEquals('US', $store->country);
    }

    public function test_geocode_location_works_with_venue(): void
    {
        $this->fakeNominatimResponse();

        $venue = Venue::create([
            'name' => 'Test Venue',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($venue);
        $venue->refresh();

        $this->assertEquals('44.9778', $venue->latitude);
        $this->assertEquals('-93.2650', $venue->longitude);
    }

    public function test_geocode_location_works_with_brewery(): void
    {
        $this->fakeNominatimResponse();

        $brewery = Brewery::create([
            'name' => 'Test Brewery',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($brewery);
        $brewery->refresh();

        $this->assertEquals('44.9778', $brewery->latitude);
        $this->assertEquals('-93.2650', $brewery->longitude);
    }

    public function test_geocode_location_works_with_store(): void
    {
        $this->fakeNominatimResponse();

        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Minneapolis',
        ]);

        GeocodeLocation::dispatchSync($store);
        $store->refresh();

        $this->assertEquals('44.9778', $store->latitude);
        $this->assertEquals('-93.2650', $store->longitude);
    }

    // ─── GeocodingService ────────────────────────────────────────────

    public function test_geocoding_service_returns_null_when_no_parts(): void
    {
        $result = GeocodingService::geocode(null, null, null);

        $this->assertNull($result);
    }

    public function test_geocoding_service_calls_nominatim_with_addressdetails(): void
    {
        $this->fakeNominatimResponse();

        GeocodingService::geocode('Minneapolis', 'Minnesota', 'United States');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org')
                && $request['addressdetails'] == 1;
        });
    }

    public function test_geocoding_service_returns_lat_lng_city_state_country(): void
    {
        $this->fakeNominatimResponse();

        $result = GeocodingService::geocode('Minneapolis', 'Minnesota', null);

        $this->assertEquals(44.9778, $result['lat']);
        $this->assertEquals(-93.2650, $result['lng']);
        $this->assertEquals('Minneapolis', $result['city']);
        $this->assertEquals('Minnesota', $result['state']);
        $this->assertEquals('United States', $result['country']);
    }

    public function test_geocoding_service_caches_results(): void
    {
        $this->fakeNominatimResponse();

        GeocodingService::geocode('Minneapolis', 'Minnesota', null);

        $cacheKey = 'geocode:'.md5('Minneapolis, Minnesota');
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_geocoding_service_returns_cached_result_on_second_call(): void
    {
        $this->fakeNominatimResponse();

        $first = GeocodingService::geocode('Minneapolis', 'Minnesota', null);
        $second = GeocodingService::geocode('Minneapolis', 'Minnesota', null);

        $this->assertEquals($first, $second);
        Http::assertSentCount(1);
    }

    public function test_geocoding_service_returns_null_on_api_failure(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(null, 500),
        ]);

        $result = GeocodingService::geocode('Minneapolis', 'Minnesota', null);

        $this->assertNull($result);
    }
}
