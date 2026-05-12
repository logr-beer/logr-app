<?php

namespace Tests\Feature;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use App\Services\CatalogBeer;
use App\Services\Discord;
use App\Services\LogrDb;
use App\Services\Untappd;
use App\Services\UntappdRss;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Live API tests (skipped when credentials are missing)
    // ------------------------------------------------------------------

    public function test_untappd_beer_search(): void
    {
        $clientId = config('services.untappd.api_key');
        $clientSecret = config('services.untappd.api_secret');

        if (! $clientId || ! $clientSecret) {
            $this->markTestSkipped('Untappd API credentials not configured.');
        }

        $untappd = new Untappd($clientId, $clientSecret);
        $results = $untappd->searchBeers('IPA', 5);

        $this->assertNotEmpty($results, 'Untappd beer search returned no results.');
        $this->assertArrayHasKey('name', $results[0]);
        $this->assertArrayHasKey('brewery', $results[0]);
    }

    public function test_untappd_brewery_search(): void
    {
        $clientId = config('services.untappd.api_key');
        $clientSecret = config('services.untappd.api_secret');

        if (! $clientId || ! $clientSecret) {
            $this->markTestSkipped('Untappd API credentials not configured.');
        }

        $untappd = new Untappd($clientId, $clientSecret);
        $results = $untappd->searchBreweries('Sierra Nevada', 5);

        $this->assertNotEmpty($results, 'Untappd brewery search returned no results.');
        $this->assertArrayHasKey('name', $results[0]);
    }

    public function test_catalog_beer_search(): void
    {
        $apiKey = config('services.catalog_beer.key');

        if (! $apiKey) {
            $this->markTestSkipped('Catalog Beer API key not configured.');
        }

        $catalog = new CatalogBeer($apiKey);
        $results = $catalog->search('stout', 5, $apiKey);

        $this->assertNotEmpty($results, 'Catalog Beer search returned no results.');
        $this->assertArrayHasKey('name', $results[0]);
    }

    public function test_logr_db_beer_search(): void
    {
        $url = config('services.logr_db.url');

        if (! $url) {
            $this->markTestSkipped('LogrDb URL not configured.');
        }

        $user = User::factory()->create();
        $user->setData('logr_db_token', 'test-token');
        $user->save();

        $logrDb = LogrDb::forUser($user);

        if (! $logrDb) {
            $this->markTestSkipped('LogrDb token not available for user.');
        }

        $results = $logrDb->searchBeers('lager', 5);

        $this->assertNotEmpty($results, 'LogrDb beer search returned no results.');
        $this->assertArrayHasKey('name', $results[0]);
    }

    // ------------------------------------------------------------------
    // Faked HTTP tests (no external calls)
    // ------------------------------------------------------------------

    public function test_untappd_rss_sync_parses_feed(): void
    {
        $user = User::factory()->create();

        $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Untappd Feed</title>
    <item>
      <title>TestUser is drinking a Test IPA by Test Brewery</title>
      <link>https://untappd.com/user/testuser/checkin/123456</link>
      <pubDate>Sat, 03 May 2026 12:00:00 +0000</pubDate>
      <description><![CDATA[Tasty hop bomb]]></description>
      <guid>https://untappd.com/user/testuser/checkin/123456</guid>
    </item>
    <item>
      <title>TestUser is drinking a Dark Stout by Another Brewery at Cool Bar</title>
      <link>https://untappd.com/user/testuser/checkin/789012</link>
      <pubDate>Sat, 03 May 2026 14:00:00 +0000</pubDate>
      <description><![CDATA[Smooth and roasty]]></description>
      <guid>https://untappd.com/user/testuser/checkin/789012</guid>
    </item>
  </channel>
</rss>
XML;

        Http::fake([
            'https://untappd.test/rss/test' => Http::response($rssXml, 200),
        ]);

        $rss = new UntappdRss;
        $result = $rss->syncFeed($user, 'https://untappd.test/rss/test');

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertNull($result['error']);

        // Verify checkins were created
        $this->assertEquals(2, Checkin::where('user_id', $user->id)->count());

        // Verify beer and brewery records
        $this->assertDatabaseHas('beers', ['name' => 'Test IPA']);
        $this->assertDatabaseHas('beers', ['name' => 'Dark Stout']);
        $this->assertDatabaseHas('breweries', ['name' => 'Test Brewery']);
        $this->assertDatabaseHas('breweries', ['name' => 'Another Brewery']);

        // Verify venue was created for the location check-in
        $this->assertDatabaseHas('venues', ['name' => 'Cool Bar']);

        // Running again should skip all items (no duplicates)
        Http::fake([
            'https://untappd.test/rss/test' => Http::response($rssXml, 200),
        ]);

        $secondResult = $rss->syncFeed($user, 'https://untappd.test/rss/test');
        $this->assertEquals(0, $secondResult['imported']);
        $this->assertEquals(2, $secondResult['skipped']);
    }

    public function test_discord_send_checkin_builds_correct_embed(): void
    {
        Http::fake([
            'discord.test/webhook/*' => Http::response('', 204),
        ]);

        $user = User::factory()->create();
        $user->setData('discord_webhooks', [
            ['url' => 'https://discord.test/webhook/123', 'publish_checkins' => true],
        ]);
        $user->save();

        $brewery = Brewery::create(['name' => 'Test Brewing Co']);
        $beer = Beer::create([
            'name' => 'Hazy Wonder',
            'brewery_id' => $brewery->id,
            'style' => ['IPA', 'Hazy'],
            'abv' => 6.5,
            'ibu' => 45,
        ]);
        $checkin = Checkin::create([
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 4.5,
            'notes' => 'Great haze!',
            'serving_type' => 'draft',
        ]);

        $result = Discord::sendCheckin($checkin, $user);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            $body = $request->data();

            // Must have embeds array
            if (empty($body['embeds']) || ! is_array($body['embeds'])) {
                return false;
            }

            $embed = $body['embeds'][0];

            // Title should contain the username
            if (! str_contains($embed['title'], 'Check-in for')) {
                return false;
            }

            // Description should contain beer name and brewery
            if (! str_contains($embed['description'], 'Hazy Wonder')) {
                return false;
            }
            if (! str_contains($embed['description'], 'Test Brewing Co')) {
                return false;
            }

            // Should have style, ABV, IBU fields
            $fieldNames = collect($embed['fields'])->pluck('name')->toArray();
            if (! in_array('Style', $fieldNames) || ! in_array('ABV', $fieldNames) || ! in_array('IBU', $fieldNames)) {
                return false;
            }

            return true;
        });
    }
}
