<?php

namespace Tests\Feature\CrossStack;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cross-stack test: drives a real /api/submissions POST against a running logr-pub
 * instance to verify the submit-on-miss path end-to-end. Skipped if logr-pub is
 * unreachable, so safe to keep in the default suite.
 *
 * The matched-case (verifying that a known beer in logr-pub backfills pub_uuid into
 * logr-app's local Beer) is not covered because logr-pub has no API for seeding
 * canonical beers without admin access. The submit-on-miss path is the higher-value
 * regression — fakes can't catch wire-level drift (renamed routes, changed payload
 * shape, broken auth).
 *
 * Caveat: each run registers a fresh user in logr-pub's dev DB and can't delete
 * itself (no user-delete API). Acceptable for local dev; periodically clean
 *
 * up `crosstest-*@logr.test` users in pub if they accumulate.
 */
class UntappdRssSubmitsUnmatchedTest extends TestCase
{
    use RefreshDatabase;

    private string $pubUrl;

    private string $pubToken;

    private ?string $fixturePath = null;

    private string $uniqueBeerName;

    private string $uniqueBreweryName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubUrl = rtrim((string) config('services.logr.pub_url'), '/');
        if (! $this->pubUrl) {
            $this->markTestSkipped('LOGR_PUB_URL not configured.');
        }

        try {
            $probe = Http::withoutVerifying()->timeout(3)->acceptJson()->get($this->pubUrl.'/api/breweries');
            if ($probe->status() >= 500) {
                $this->markTestSkipped("logr-pub returned {$probe->status()} at {$this->pubUrl}");
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('logr-pub unreachable: '.$e->getMessage());
        }

        $email = 'rss-crosstest-'.bin2hex(random_bytes(4)).'@logr.test';
        $register = Http::withoutVerifying()->acceptJson()->post(
            $this->pubUrl.'/api/auth/register',
            [
                'email' => $email,
                'password' => 'crosstest-password',
                'password_confirmation' => 'crosstest-password',
            ]
        );

        if (! $register->successful() || ! $register->json('token')) {
            $this->markTestSkipped('Could not register pub user: '.$register->status().' '.$register->body());
        }

        $this->pubToken = $register->json('token');

        $suffix = bin2hex(random_bytes(4));
        $this->uniqueBeerName = "Crosstest Fake IPA {$suffix}";
        $this->uniqueBreweryName = "Crosstest Phantom Brewery {$suffix}";
    }

    protected function tearDown(): void
    {
        if ($this->fixturePath && file_exists($this->fixturePath)) {
            @unlink($this->fixturePath);
        }

        if (isset($this->pubToken)) {
            Http::withoutVerifying()
                ->withToken($this->pubToken)
                ->acceptJson()
                ->delete($this->pubUrl.'/api/auth/token');
        }

        parent::tearDown();
    }

    public function test_unmatched_rss_beer_creates_submission_in_logr_pub(): void
    {
        $this->writeFixture();
        $fixtureUrl = rtrim((string) config('app.url'), '/').'/test-fixtures/'.basename($this->fixturePath);

        Setting::set('pub_api_key', $this->pubToken);

        $user = User::factory()->create();
        $user->setData('pub_secret_key', $this->pubToken);
        $user->setData('untappd_rss_feeds', [
            ['url' => $fixtureUrl, 'label' => 'crosstest', 'submit_to_logrdb' => true],
        ]);
        $user->save();

        $this->artisan('untappd:sync', ['--user' => $user->id])->assertExitCode(0);

        $beer = Beer::where('name', $this->uniqueBeerName)->first();
        $this->assertNotNull($beer, 'Local Beer not created');
        $this->assertNull($beer->pub_uuid, 'Local Beer should not have pub_uuid (unmatched)');

        $brewery = Brewery::where('name', $this->uniqueBreweryName)->first();
        $this->assertNotNull($brewery, 'Local Brewery not created');
        $this->assertNull($brewery->pub_uuid, 'Local Brewery should not have pub_uuid (unmatched)');

        $resp = Http::withoutVerifying()
            ->withToken($this->pubToken)
            ->acceptJson()
            ->get($this->pubUrl.'/api/submissions');

        $this->assertTrue($resp->successful(), 'GET /api/submissions failed: '.$resp->status().' '.$resp->body());

        $rows = $resp->json('data') ?? $resp->json() ?? [];
        $match = collect($rows)->first(
            fn ($s) => ($s['data']['name'] ?? null) === $this->uniqueBeerName
        );

        $this->assertNotNull($match, 'No matching submission on logr-pub. Body: '.$resp->body());
        $this->assertSame('beer', $match['type']);
        $this->assertSame('pending', $match['status']);
        $this->assertSame($this->uniqueBreweryName, $match['data']['brewery_name'] ?? null);
    }

    private function writeFixture(): void
    {
        $dir = public_path('test-fixtures');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->fixturePath = $dir.'/crosstest-'.bin2hex(random_bytes(4)).'.xml';

        $guid = 'crosstest-'.bin2hex(random_bytes(4));
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Crosstest</title>
    <item>
      <title>Crosstester is drinking a {$this->uniqueBeerName} by {$this->uniqueBreweryName} at TestPub</title>
      <guid isPermaLink="false">{$guid}</guid>
      <link>https://untappd.example/{$guid}</link>
      <pubDate>Tue, 19 May 2026 22:00:00 +0000</pubDate>
      <description><![CDATA[fixture]]></description>
    </item>
  </channel>
</rss>
XML;
        file_put_contents($this->fixturePath, $xml);
    }
}
