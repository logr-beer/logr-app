<?php

namespace App\Console\Commands;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Companion;
use App\Models\Inventory;
use App\Models\Tag;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeData extends Command
{
    protected $signature = 'logr:purge {--demo : Re-seed demo data after purging} {--force : Skip confirmation}';

    protected $description = 'Purge all beer data while keeping user accounts intact';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete all beers, check-ins, inventory, collections, and related data. User accounts will be preserved. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->purge();

        if ($this->option('demo')) {
            $this->info('Seeding demo data...');
            (new \Database\Seeders\DemoSeeder)->run();
            $this->info('Demo data loaded.');
        }

        $this->info('Purge complete.');

        return self::SUCCESS;
    }

    public function purge(): void
    {
        $this->info('Purging all data...');

        // Delete in order to respect foreign key constraints
        DB::table('beer_collection')->delete();
        DB::table('checkin_companion')->delete();
        DB::table('taggables')->delete();
        DB::table('checkin_photos')->delete();

        Inventory::query()->delete();
        Collection::query()->delete();
        Checkin::query()->delete();
        Companion::query()->delete();
        Tag::query()->delete();
        Beer::query()->delete();
        Brewery::query()->delete();
        Venue::query()->delete();

        // Re-create default Home venue
        Venue::create(['name' => 'Home']);

        // Clean up uploaded files
        Storage::deleteDirectory('photos');
        Storage::deleteDirectory('logos');

        $this->info('All data purged. User accounts preserved.');
    }
}
