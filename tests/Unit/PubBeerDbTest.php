<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\User;
use App\Services\PubBeerDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PubBeerDbTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_instance_returns_null_without_key(): void
    {
        $this->assertNull(PubBeerDb::forInstance());
    }

    public function test_for_instance_returns_service_with_key(): void
    {
        Setting::set('pub_api_key', 'test-key');

        $this->assertInstanceOf(PubBeerDb::class, PubBeerDb::forInstance());
    }

    public function test_provision_key_stores_token(): void
    {
        Http::fake([
            '*/api/instances' => Http::response(['api_key' => 'new-instance-key'], 201),
        ]);

        $token = PubBeerDb::provisionKey();

        $this->assertEquals('new-instance-key', $token);
        $this->assertEquals('new-instance-key', Setting::get('pub_api_key'));
    }

    public function test_provision_key_returns_null_on_failure(): void
    {
        Setting::clearCache();

        Http::fake([
            '*/api/instances' => Http::response('Server Error', 500),
        ]);

        $this->assertNull(PubBeerDb::provisionKey());
        $this->assertNull(Setting::get('pub_api_key'));
    }

    public function test_search_beers_returns_mapped_results(): void
    {
        Setting::set('pub_api_key', 'test-key');

        Http::fake([
            '*/api/beers*' => Http::response([
                'data' => [
                    [
                        'id' => 'uuid-1',
                        'name' => 'Test IPA',
                        'styles' => ['IPA'],
                        'abv' => 6.5,
                        'ibu' => 55,
                        'description' => 'A test beer',
                        'brewery' => [
                            'id' => 'brewery-uuid',
                            'name' => 'Test Brewery',
                            'city' => 'Portland',
                            'state' => 'OR',
                            'country' => 'US',
                            'website' => null,
                        ],
                    ],
                ],
            ]),
        ]);

        $pub = PubBeerDb::forInstance();
        $results = $pub->searchBeers('IPA', 5);

        $this->assertCount(1, $results);
        $this->assertEquals('Test IPA', $results[0]['name']);
        $this->assertEquals('uuid-1', $results[0]['id']);
        $this->assertEquals('Test Brewery', $results[0]['brewery_name']);
        $this->assertEquals(6.5, $results[0]['abv']);
    }

    public function test_search_breweries_returns_mapped_results(): void
    {
        Setting::set('pub_api_key', 'test-key');

        Http::fake([
            '*/api/breweries*' => Http::response([
                'data' => [
                    [
                        'id' => 'brewery-uuid',
                        'name' => 'Test Brewery',
                        'city' => 'Portland',
                        'state' => 'OR',
                        'country' => 'US',
                        'website' => 'https://test.com',
                    ],
                ],
            ]),
        ]);

        $pub = PubBeerDb::forInstance();
        $results = $pub->searchBreweries('Test', 5);

        $this->assertCount(1, $results);
        $this->assertEquals('Test Brewery', $results[0]['name']);
        $this->assertEquals('Portland', $results[0]['city']);
    }

    public function test_handle_secret_key_revoked_clears_user_key(): void
    {
        $user = User::factory()->create();
        $user->setData('pub_secret_key', 'old-token');
        $user->save();

        PubBeerDb::handleSecretKeyRevoked($user->id);

        $user->refresh();
        $this->assertNull($user->getData('pub_secret_key'));
    }

    public function test_request_returns_null_on_api_failure(): void
    {
        Setting::set('pub_api_key', 'test-key');

        Http::fake([
            '*/api/beers*' => Http::response('Not Found', 404),
        ]);

        $pub = PubBeerDb::forInstance();
        $results = $pub->searchBeers('nope', 5);

        $this->assertEmpty($results);
    }
}
