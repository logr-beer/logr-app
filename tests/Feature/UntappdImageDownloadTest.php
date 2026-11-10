<?php

namespace Tests\Feature;

use App\Models\Beer;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\User;
use App\Services\UntappdRss;
use App\Services\UntappdScraper;
use App\Support\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UntappdImageDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const FEED_URL = 'https://untappd.com/rss/user/demo?key=abc';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        SafeUrl::resolveUsing(fn () => ['93.184.216.34']);
    }

    private function feedWithPhoto(string $photoUrl): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>Demo is drinking a Test IPA by Test Brewery</title>
      <link>https://untappd.com/user/demo/checkin/1</link>
      <pubDate>Fri, 02 May 2026 18:30:00 +0000</pubDate>
      <description><![CDATA[<img src="{$photoUrl}" /> Nice]]></description>
      <guid>https://untappd.com/user/demo/checkin/1</guid>
    </item>
  </channel>
</rss>
XML;
    }

    public function test_rss_photo_from_untappd_cdn_is_saved(): void
    {
        $photoUrl = 'https://untappd.akamaized.net/photos/2026_05_02/photo.png';
        Http::fake([
            'untappd.com/rss/*' => Http::response($this->feedWithPhoto($photoUrl)),
            'untappd.akamaized.net/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
        ]);

        $result = (new UntappdRss)->syncFeed(User::factory()->create(), self::FEED_URL);

        $this->assertSame(1, $result['imported']);
        $photo = CheckinPhoto::sole();
        $this->assertStringEndsWith('.png', $photo->photo_path);
        Storage::disk('public')->assertExists($photo->photo_path);
        $this->assertSame($photo->photo_path, Beer::sole()->photo_path);
    }

    public function test_rss_photo_from_other_host_is_skipped(): void
    {
        Http::fake([
            'untappd.com/rss/*' => Http::response($this->feedWithPhoto('https://internal.example/secret.jpg')),
            '*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
        ]);

        $result = (new UntappdRss)->syncFeed(User::factory()->create(), self::FEED_URL);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, Checkin::count());
        $this->assertSame(0, CheckinPhoto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'internal.example'));
    }

    public function test_rss_photo_that_is_not_an_image_is_not_stored(): void
    {
        Http::fake([
            'untappd.com/rss/*' => Http::response($this->feedWithPhoto('https://untappd.akamaized.net/photos/x.jpg')),
            'untappd.akamaized.net/*' => Http::response('<html>nope</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        (new UntappdRss)->syncFeed(User::factory()->create(), self::FEED_URL);

        $this->assertSame(0, CheckinPhoto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_rss_feed_url_must_be_public_https(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $result = (new UntappdRss)->syncFeed($user, 'http://untappd.com/rss/user/demo');
        $this->assertNotNull($result['error']);

        SafeUrl::resolveUsing(fn () => ['127.0.0.1']);
        $result = (new UntappdRss)->syncFeed($user, self::FEED_URL);
        $this->assertNotNull($result['error']);

        Http::assertNothingSent();
    }

    public function test_settings_form_only_accepts_https_feed_urls(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('profile.api-settings-form')
            ->set('newFeedUrl', 'http://untappd.com/rss/user/demo')
            ->call('addFeed')
            ->assertHasErrors(['newFeedUrl'])
            ->set('newFeedUrl', self::FEED_URL)
            ->call('addFeed')
            ->assertHasNoErrors()
            ->assertSet('rssFeeds.0.url', self::FEED_URL);
    }

    public function test_scraper_label_download_uses_allow_list_and_image_check(): void
    {
        Http::fake([
            'assets.untappd.com/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
            'internal.example/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
            'untappd.akamaized.net/*' => Http::response('not an image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $scraper = new class extends UntappdScraper
        {
            public function download(Beer $beer, string $url): void
            {
                $this->downloadLabel($beer, $url);
            }
        };

        $blocked = Beer::create(['name' => 'Blocked']);
        $scraper->download($blocked, 'https://internal.example/label.png');
        $this->assertNull($blocked->fresh()->photo_path);

        $bogus = Beer::create(['name' => 'Bogus']);
        $scraper->download($bogus, 'https://untappd.akamaized.net/site/beer_logos/x.jpeg');
        $this->assertNull($bogus->fresh()->photo_path);

        $valid = Beer::create(['name' => 'Valid']);
        $scraper->download($valid, 'https://assets.untappd.com/site/beer_logos/label.png');
        $path = $valid->fresh()->photo_path;
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }
}
