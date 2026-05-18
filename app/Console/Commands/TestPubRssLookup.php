<?php

namespace App\Console\Commands;

use App\Services\PubBeerDb;
use App\Services\UntappdRss;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPubRssLookup extends Command
{
    protected $signature = 'test:pub-rss {url : RSS feed URL} {--limit=5 : Max items to test}';

    protected $description = 'Test Pub API beer/brewery lookups against an RSS feed (dry run, no imports)';

    public function handle(): int
    {
        $pub = PubBeerDb::forInstance();
        if (! $pub) {
            $this->error('Pub API not configured. Check pub_url and pub_api_key.');

            return 1;
        }

        $url = $this->argument('url');
        $limit = (int) $this->option('limit');

        $this->info('Fetching RSS feed...');

        $response = Http::withHeaders(['User-Agent' => config('logr.user_agent')])
            ->timeout(30)
            ->get($url);

        if ($response->failed()) {
            $this->error("Failed to fetch RSS feed (HTTP {$response->status()}).");

            return 1;
        }

        $xml = @simplexml_load_string($response->body());
        if (! $xml || ! isset($xml->channel->item)) {
            $this->error('Invalid RSS feed format.');

            return 1;
        }

        $rss = new UntappdRss;
        $parseItem = new \ReflectionMethod($rss, 'parseItem');

        $count = 0;
        $found = 0;
        $notFound = 0;

        foreach ($xml->channel->item as $item) {
            if ($count >= $limit) {
                break;
            }

            $parsed = $parseItem->invoke($rss, $item);
            if (! $parsed) {
                continue;
            }

            $count++;
            $this->newLine();
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("#{$count}: {$parsed['beer']} by {$parsed['brewery']}");

            // Search for the beer by name
            $query = "{$parsed['beer']} {$parsed['brewery']}";
            $this->line("  Search query: \"{$query}\"");

            $results = $pub->searchBeers($query, 3);

            if (empty($results)) {
                $this->warn('  ✗ No Pub results found');

                // Try searching just the beer name
                $this->line("  Retrying with just beer name: \"{$parsed['beer']}\"");
                $results = $pub->searchBeers($parsed['beer'], 3);
            }

            if (! empty($results)) {
                foreach ($results as $i => $beer) {
                    $match = strtolower($beer['name']) === strtolower($parsed['beer'])
                        && strtolower($beer['brewery_name'] ?? '') === strtolower($parsed['brewery']);

                    $marker = $match ? '✓ MATCH' : '  ~';
                    $this->line("  {$marker} [{$beer['id']}] {$beer['name']} — {$beer['brewery_name']}");

                    if ($beer['style']) {
                        $this->line("         Style: {$beer['style']}");
                    }
                    if ($beer['abv']) {
                        $this->line("         ABV: {$beer['abv']}%");
                    }

                    if ($match) {
                        $found++;
                        break;
                    }
                }

                if (! collect($results)->contains(fn ($b) => strtolower($b['name']) === strtolower($parsed['beer'])
                    && strtolower($b['brewery_name'] ?? '') === strtolower($parsed['brewery']))) {
                    $this->warn('  ✗ No exact match (showing closest results above)');
                    $notFound++;
                }
            } else {
                $this->warn('  ✗ No results at all');
                $notFound++;
            }

            // Also try brewery search
            $breweryResults = $pub->searchBreweries($parsed['brewery'], 2);
            if (! empty($breweryResults)) {
                $b = $breweryResults[0];
                $brewMatch = strtolower($b['name']) === strtolower($parsed['brewery']);
                $marker = $brewMatch ? '✓' : '~';
                $location = implode(', ', array_filter([$b['city'], $b['state'], $b['country']]));
                $this->line("  Brewery {$marker}: [{$b['id']}] {$b['name']}".($location ? " ({$location})" : ''));
            } else {
                $this->line('  Brewery: not found in Pub');
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Results: {$count} items tested, {$found} exact matches, {$notFound} not found");

        return 0;
    }
}
