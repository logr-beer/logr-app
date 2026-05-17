<?php

namespace Tests\Unit;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\User;
use App\Services\PubBeerDb;
use App\Services\PubBeerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PubBeerResolverTest extends TestCase
{
    use RefreshDatabase;

    private function setupPubInstance(): void
    {
        config(['services.logr.pub_url' => 'https://pub.test']);
        \App\Models\Setting::set('pub_api_key', 'test-instance-key');
    }

    public function test_make_returns_null_without_pub_configured(): void
    {
        config(['services.logr.pub_url' => '']);

        $this->assertNull(PubBeerResolver::make());
    }

    public function test_make_returns_resolver_when_pub_configured(): void
    {
        $this->setupPubInstance();

        $this->assertInstanceOf(PubBeerResolver::class, PubBeerResolver::make());
    }

    public function test_resolve_brewery_backfills_pub_uuid_on_match(): void
    {
        $this->setupPubInstance();

        Http::fake([
            'pub.test/api/breweries*' => Http::response(['data' => [
                ['id' => 'brew-uuid-1', 'name' => 'Test Brewing', 'city' => 'Portland', 'state' => 'OR', 'country' => 'US', 'website' => 'https://test.beer'],
            ]]),
        ]);

        $resolver = PubBeerResolver::make();
        $brewery = $resolver->resolveBrewery('Test Brewing');

        $this->assertEquals('brew-uuid-1', $brewery->pub_uuid);
        $this->assertEquals('Portland', $brewery->city);
        $this->assertEquals('OR', $brewery->state);
    }

    public function test_resolve_brewery_skips_lookup_when_pub_uuid_exists(): void
    {
        $this->setupPubInstance();
        Brewery::create(['name' => 'Already Linked', 'pub_uuid' => 'existing-uuid']);

        Http::fake();

        $resolver = PubBeerResolver::make();
        $brewery = $resolver->resolveBrewery('Already Linked');

        $this->assertEquals('existing-uuid', $brewery->pub_uuid);
        Http::assertNothingSent();
    }

    public function test_resolve_brewery_creates_locally_on_no_match(): void
    {
        $this->setupPubInstance();

        Http::fake([
            'pub.test/api/breweries*' => Http::response(['data' => []]),
        ]);

        $resolver = PubBeerResolver::make();
        $brewery = $resolver->resolveBrewery('Unknown Brewing');

        $this->assertNull($brewery->pub_uuid);
        $this->assertEquals('Unknown Brewing', $brewery->name);
    }

    public function test_resolve_beer_backfills_pub_uuid_on_match(): void
    {
        $this->setupPubInstance();
        $brewery = Brewery::create(['name' => 'Test Brewing']);

        Http::fake([
            'pub.test/api/search*' => Http::response([
                'beers' => [
                    ['id' => 'beer-uuid-1', 'name' => 'Hoppy IPA', 'brewery_name' => 'Test Brewing', 'brewery_id' => 'brew-uuid-1', 'styles' => ['IPA'], 'abv' => 6.5, 'ibu' => 65, 'description' => 'A hoppy beer'],
                ],
                'breweries' => [],
            ]),
        ]);

        $resolver = PubBeerResolver::make();
        $beer = $resolver->resolveBeer('Hoppy IPA', $brewery);

        $this->assertEquals('beer-uuid-1', $beer->pub_uuid);
        $this->assertEquals(6.5, $beer->abv);
        $this->assertEquals(65, $beer->ibu);
        $this->assertEquals('A hoppy beer', $beer->description);
    }

    public function test_resolve_beer_skips_lookup_when_pub_uuid_exists(): void
    {
        $this->setupPubInstance();
        $brewery = Brewery::create(['name' => 'Test Brewing']);
        Beer::create(['name' => 'Already Linked', 'brewery_id' => $brewery->id, 'pub_uuid' => 'existing-beer-uuid']);

        Http::fake();

        $resolver = PubBeerResolver::make();
        $beer = $resolver->resolveBeer('Already Linked', $brewery);

        $this->assertEquals('existing-beer-uuid', $beer->pub_uuid);
        Http::assertNothingSent();
    }

    public function test_resolve_beer_creates_locally_on_no_match(): void
    {
        $this->setupPubInstance();
        $brewery = Brewery::create(['name' => 'Test Brewing']);

        Http::fake([
            'pub.test/api/search*' => Http::response(['beers' => [], 'breweries' => []]),
        ]);

        $resolver = PubBeerResolver::make();
        $beer = $resolver->resolveBeer('New Beer', $brewery, ['abv' => 5.0, 'style' => 'Lager']);

        $this->assertNull($beer->pub_uuid);
        $this->assertEquals('New Beer', $beer->name);
        $this->assertEquals(5.0, $beer->abv);
    }

    public function test_submit_unmatched_sends_submission_with_secret_key(): void
    {
        $this->setupPubInstance();
        $user = User::factory()->create();
        $user->setData('pub_secret_key', 'user-secret-key');
        $user->save();

        $brewery = Brewery::create(['name' => 'Test Brewing', 'pub_uuid' => 'brew-uuid-1']);
        $beer = Beer::create(['name' => 'New Beer', 'brewery_id' => $brewery->id, 'abv' => 5.5]);

        Http::fake([
            'pub.test/api/submissions' => Http::response(['id' => 'sub-1', 'status' => 'pending']),
        ]);

        $resolver = new PubBeerResolver(PubBeerDb::forInstance(), $user);
        $resolver->submitUnmatched($beer, $brewery);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pub.test/api/submissions'
                && $request->data()['type'] === 'beer'
                && $request->data()['data']['name'] === 'New Beer'
                && $request->data()['data']['brewery_name'] === 'Test Brewing'
                && $request->data()['data']['brewery_id'] === 'brew-uuid-1'
                && $request->data()['data']['abv'] === 5.5;
        });
    }

    public function test_submit_unmatched_does_nothing_without_secret_key(): void
    {
        $this->setupPubInstance();
        $user = User::factory()->create();

        $brewery = Brewery::create(['name' => 'Test Brewing']);
        $beer = Beer::create(['name' => 'New Beer', 'brewery_id' => $brewery->id]);

        Http::fake();

        $resolver = new PubBeerResolver(PubBeerDb::forInstance(), $user);
        $resolver->submitUnmatched($beer, $brewery);

        Http::assertNothingSent();
    }

    public function test_submit_unmatched_revokes_key_on_401(): void
    {
        $this->setupPubInstance();
        $user = User::factory()->create();
        $user->setData('pub_secret_key', 'expired-key');
        $user->save();

        $brewery = Brewery::create(['name' => 'Test Brewing']);
        $beer = Beer::create(['name' => 'New Beer', 'brewery_id' => $brewery->id]);

        Http::fake([
            'pub.test/api/submissions' => Http::response([], 401),
        ]);

        $resolver = new PubBeerResolver(PubBeerDb::forInstance(), $user);
        $resolver->submitUnmatched($beer, $brewery);

        $user->refresh();
        $this->assertNull($user->getData('pub_secret_key'));
    }

    public function test_resolve_beer_backfills_brewery_pub_uuid_from_beer_match(): void
    {
        $this->setupPubInstance();
        $brewery = Brewery::create(['name' => 'Test Brewing']);

        Http::fake([
            'pub.test/api/search*' => Http::response([
                'beers' => [
                    ['id' => 'beer-uuid-1', 'name' => 'Hoppy IPA', 'brewery_name' => 'Test Brewing', 'brewery_id' => 'brew-uuid-from-beer', 'styles' => null, 'abv' => null, 'ibu' => null, 'description' => null],
                ],
                'breweries' => [],
            ]),
        ]);

        $resolver = PubBeerResolver::make();
        $resolver->resolveBeer('Hoppy IPA', $brewery);

        $brewery->refresh();
        $this->assertEquals('brew-uuid-from-beer', $brewery->pub_uuid);
    }
}
