<?php

namespace App\Console\Commands;

use App\Models\Beer;
use App\Services\UntappdScraper;
use Illuminate\Console\Command;

class EnrichBeersUntappd extends Command
{
    protected $signature = 'untappd:enrich-beers
        {beer? : Beer ID to enrich (one-off)}
        {--missing : Only enrich beers without an untappd_id}
        {--all : Enrich all beers (15s delay between each)}';

    protected $description = 'Enrich beer records with Untappd ratings and metadata. One-off by default; use --all or --missing for bulk (1 per 15s).';

    public function handle(UntappdScraper $scraper): int
    {
        $beerId = $this->argument('beer');
        $all = $this->option('all');
        $missing = $this->option('missing');

        if (! $beerId && ! $all && ! $missing) {
            $this->error('Provide a beer ID, or use --missing or --all for bulk enrichment.');

            return self::FAILURE;
        }

        // One-off single beer
        if ($beerId) {
            $beer = Beer::with('brewery')->find($beerId);

            if (! $beer) {
                $this->error("Beer ID {$beerId} not found.");

                return self::FAILURE;
            }

            $this->info("Enriching: {$beer->name} (".($beer->brewery?->name ?? 'unknown brewery').')');
            $result = $scraper->enrichBeer($beer);
            $this->line("  → {$result['message']}");

            return self::SUCCESS;
        }

        // Bulk
        $query = Beer::with('brewery')->orderBy('id');

        if ($missing) {
            $query->where(function ($q) {
                $q->whereNull('data')->orWhereRaw("JSON_EXTRACT(data, '$.untappd.id') IS NULL");
            });
            $this->info('Enriching beers without Untappd data (1 per 15s)...');
        } else {
            $this->info('Enriching all beers (1 per 15s)...');
        }

        $total = $query->count();
        $this->info("Found {$total} beers to process.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if (! $this->confirm("This will make up to {$total} requests at 15s each (~".ceil($total * 15 / 60).' min). Continue?')) {
            return self::SUCCESS;
        }

        $processed = 0;
        $matched = 0;
        $updated = 0;

        $query->chunk(50, function ($beers) use ($scraper, $total, &$processed, &$matched, &$updated) {
            foreach ($beers as $beer) {
                $processed++;
                $breweryName = $beer->brewery?->name ?? 'unknown';
                $this->line("[{$processed}/{$total}] {$beer->name} ({$breweryName})");

                $result = $scraper->enrichBeer($beer);
                $this->line("  → {$result['message']}");

                if ($result['matched']) {
                    $matched++;
                }
                if ($result['updated']) {
                    $updated++;
                }

                if ($processed < $total) {
                    sleep(15);
                }
            }
        });

        $this->newLine();
        $this->info("Done. Processed: {$processed}, Matched: {$matched}, Updated: {$updated}");

        return self::SUCCESS;
    }
}
