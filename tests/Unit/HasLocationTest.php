<?php

namespace Tests\Unit;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_location_returns_city_state_country(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'US',
        ]);

        $this->assertEquals('Portland, Oregon, US', $store->displayLocation());
    }

    public function test_display_location_with_only_city(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
            'city' => 'Portland',
        ]);

        $this->assertEquals('Portland', $store->displayLocation());
    }

    public function test_display_location_with_state_and_country(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
            'state' => 'Oregon',
            'country' => 'US',
        ]);

        $this->assertEquals('Oregon, US', $store->displayLocation());
    }

    public function test_display_location_with_only_country(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
            'country' => 'US',
        ]);

        $this->assertEquals('US', $store->displayLocation());
    }

    public function test_display_location_returns_empty_string_when_no_location_fields(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
        ]);

        $this->assertEquals('', $store->displayLocation());
    }

    public function test_scope_with_coordinates_filters_to_records_with_lat_and_lng(): void
    {
        $withCoords = Store::create([
            'name' => 'With Coords',
            'latitude' => 45.5152,
            'longitude' => -122.6784,
        ]);

        Store::create([
            'name' => 'Without Coords',
        ]);

        $results = Store::withCoordinates()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($withCoords->id, $results->first()->id);
    }

    public function test_scope_without_coordinates_filters_to_records_without_lat(): void
    {
        Store::create([
            'name' => 'With Coords',
            'latitude' => 45.5152,
            'longitude' => -122.6784,
        ]);

        $withoutCoords = Store::create([
            'name' => 'Without Coords',
        ]);

        $results = Store::withoutCoordinates()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($withoutCoords->id, $results->first()->id);
    }

    public function test_scope_geocodable_returns_records_without_lat_that_have_city(): void
    {
        $geocodable = Store::create([
            'name' => 'Geocodable Store',
            'city' => 'Portland',
        ]);

        Store::create([
            'name' => 'Already Geocoded',
            'city' => 'Portland',
            'latitude' => 45.5152,
            'longitude' => -122.6784,
        ]);

        $results = Store::geocodable()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($geocodable->id, $results->first()->id);
    }

    public function test_scope_geocodable_returns_records_without_lat_that_have_state(): void
    {
        $geocodable = Store::create([
            'name' => 'Some Store',
            'state' => 'Oregon',
        ]);

        $results = Store::geocodable()->get();

        $this->assertTrue($results->contains($geocodable->id));
    }

    public function test_scope_geocodable_returns_records_without_lat_that_have_name(): void
    {
        $geocodable = Store::create([
            'name' => 'Named Store',
        ]);

        $results = Store::geocodable()->get();

        $this->assertTrue($results->contains($geocodable->id));
    }

    public function test_scope_geocodable_excludes_records_with_coordinates(): void
    {
        Store::create([
            'name' => 'Already Geocoded',
            'city' => 'Portland',
            'latitude' => 45.5152,
            'longitude' => -122.6784,
        ]);

        $results = Store::geocodable()->get();

        $this->assertCount(0, $results->where('name', 'Already Geocoded'));
    }
}
