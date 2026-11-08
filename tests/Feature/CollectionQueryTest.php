<?php

namespace Tests\Feature;

use App\Livewire\BeerShow;
use App\Livewire\CollectionIndex;
use App\Models\Beer;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $venue = Venue::create(['name' => 'The Taproom']);
        $ipa = Beer::create(['name' => 'Hoppy', 'style' => ['IPA'], 'abv' => 6.5, 'is_favorite' => true]);
        $stout = Beer::create(['name' => 'Dark', 'style' => ['Stout'], 'abv' => 9.0]);
        Beer::create(['name' => 'Untouched', 'style' => ['Lager'], 'abv' => 4.5]);

        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $ipa->id, 'rating' => 4.5, 'serving_type' => 'draft', 'venue_id' => $venue->id, 'created_at' => '2025-06-01 12:00:00']);
        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $ipa->id, 'rating' => 4.0, 'serving_type' => 'can', 'created_at' => '2025-07-01 12:00:00']);
        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $stout->id, 'rating' => 3.0, 'serving_type' => 'draft', 'created_at' => '2024-03-01 12:00:00']);

        Inventory::create(['user_id' => $this->user->id, 'beer_id' => $stout->id, 'quantity' => 2, 'storage_location' => 'Cellar']);

        foreach ([
            ['style' => 'IPA'],
            ['min_rating' => 4.0],
            ['favorites' => true],
            ['oldest_in_stock' => true],
            ['min_abv' => 6.0],
            ['max_abv' => 7.0],
            ['serving_type' => 'draft'],
            ['venue' => 'Taproom'],
            ['brewery' => 'Nobody'],
            ['min_abv' => 5.0, 'serving_type' => 'draft'],
        ] as $i => $rules) {
            Collection::create(['user_id' => $this->user->id, 'name' => "Dynamic {$i}", 'is_dynamic' => true, 'rules' => $rules]);
        }
    }

    public function test_collection_index_renders_within_query_budget_without_writes(): void
    {
        $component = Livewire::actingAs($this->user)->test(CollectionIndex::class);

        // mount() created the built-in year (2024, 2025) and storage (Cellar) collections
        $this->assertSame(13, Collection::where('user_id', $this->user->id)->where('is_dynamic', true)->count());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->set('search', 'Dynamic');
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $queries->count(), $queries->implode("\n"));
        $this->assertEmpty(
            $queries->filter(fn ($sql) => preg_match('/^\s*(insert|update|delete)/i', $sql)),
            'Re-render must not write to the database',
        );
        $component->assertSee('Dynamic 9');
    }

    public function test_batched_counts_match_individual_counts(): void
    {
        $collections = Collection::where('user_id', $this->user->id)->get();
        $counts = Collection::dynamicBeerCounts($collections);

        foreach ($collections as $collection) {
            $this->assertSame($collection->resolveBeersCount(), $counts[$collection->id], json_encode($collection->rules));
        }

        $this->assertSame([], Collection::dynamicBeerCounts([]));
    }

    public function test_dynamic_counts_shown_on_index(): void
    {
        Livewire::actingAs($this->user)
            ->test(CollectionIndex::class)
            ->assertViewHas('dynamicCollections', function ($collections) {
                $byName = $collections->pluck('dynamic_count', 'name');

                return $byName['Dynamic 0'] === 1   // IPA
                    && $byName['Dynamic 1'] === 1   // avg rating >= 4
                    && $byName['Dynamic 6'] === 2   // draft
                    && $byName['Dynamic 8'] === 0   // no such brewery
                    && $byName['2025 Check-ins'] === 1
                    && $byName['Cellar'] === 1;
            });
    }

    public function test_empty_storage_collection_is_removed_on_mount(): void
    {
        Livewire::actingAs($this->user)->test(CollectionIndex::class);
        $this->assertDatabaseHas('collections', ['user_id' => $this->user->id, 'name' => 'Cellar']);

        Inventory::where('storage_location', 'Cellar')->update(['quantity' => 0]);

        Livewire::actingAs($this->user)->test(CollectionIndex::class);
        $this->assertDatabaseMissing('collections', ['user_id' => $this->user->id, 'name' => 'Cellar']);
    }

    public function test_beer_page_lists_matching_dynamic_collections(): void
    {
        $ipa = Beer::where('name', 'Hoppy')->first();

        Livewire::actingAs($this->user)
            ->test(CollectionIndex::class);

        Livewire::actingAs($this->user)
            ->test(BeerShow::class, ['beer' => $ipa])
            ->assertViewHas('beerCollections', function ($collections) {
                return $collections->pluck('name')->sort()->values()->all() === [
                    '2025 Check-ins', 'Dynamic 0', 'Dynamic 1', 'Dynamic 2', 'Dynamic 4', 'Dynamic 5', 'Dynamic 6', 'Dynamic 7', 'Dynamic 9',
                ];
            });
    }
}
