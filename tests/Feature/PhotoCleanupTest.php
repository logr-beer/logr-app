<?php

namespace Tests\Feature;

use App\Livewire\BeerForm;
use App\Livewire\BeerIndex;
use App\Livewire\BeerShow;
use App\Livewire\CheckinIndex;
use App\Livewire\LocationShow;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function checkinWithPhoto(User $user, Beer $beer, string $path): Checkin
    {
        $checkin = Checkin::create(['user_id' => $user->id, 'beer_id' => $beer->id, 'rating' => 4.0]);
        Storage::disk('public')->put($path, 'photo');
        CheckinPhoto::create(['checkin_id' => $checkin->id, 'photo_path' => $path]);

        return $checkin;
    }

    public function test_bulk_deleting_checkins_removes_their_photo_files(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Test IPA']);
        $first = $this->checkinWithPhoto($user, $beer, 'checkin-photos/one.jpg');
        $second = $this->checkinWithPhoto($user, $beer, 'checkin-photos/two.jpg');

        Livewire::actingAs($user)
            ->test(CheckinIndex::class)
            ->set('selected', [$first->id, $second->id])
            ->call('deleteSelected');

        $this->assertDatabaseCount('checkins', 0);
        $this->assertDatabaseCount('checkin_photos', 0);
        Storage::disk('public')->assertMissing(['checkin-photos/one.jpg', 'checkin-photos/two.jpg']);
    }

    public function test_bulk_deleting_checkins_keeps_photo_used_as_beer_photo(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Test IPA', 'photo_path' => 'checkin-photos/rss.jpg']);
        $checkin = $this->checkinWithPhoto($user, $beer, 'checkin-photos/rss.jpg');

        Livewire::actingAs($user)
            ->test(CheckinIndex::class)
            ->set('selected', [$checkin->id])
            ->call('deleteSelected');

        $this->assertDatabaseCount('checkins', 0);
        Storage::disk('public')->assertExists('checkin-photos/rss.jpg');
    }

    public function test_deleting_beer_from_show_page_removes_checkin_and_beer_photos(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Test IPA', 'photo_path' => 'checkin-photos/rss.jpg']);
        $this->checkinWithPhoto($user, $beer, 'checkin-photos/rss.jpg');
        $this->checkinWithPhoto($user, $beer, 'checkin-photos/other.jpg');

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('deleteBeer');

        $this->assertDatabaseCount('checkin_photos', 0);
        Storage::disk('public')->assertMissing(['checkin-photos/rss.jpg', 'checkin-photos/other.jpg']);
    }

    public function test_deleting_beer_from_form_removes_checkin_photos(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Test IPA']);
        $this->checkinWithPhoto($user, $beer, 'checkin-photos/one.jpg');

        Livewire::actingAs($user)
            ->test(BeerForm::class, ['beer' => $beer])
            ->call('deleteBeer');

        $this->assertDatabaseCount('checkin_photos', 0);
        Storage::disk('public')->assertMissing('checkin-photos/one.jpg');
    }

    public function test_bulk_deleting_beers_removes_checkin_and_beer_photos(): void
    {
        $user = User::factory()->create();
        Storage::disk('public')->put('beers/label.jpg', 'photo');
        $beer = Beer::create(['name' => 'Test IPA', 'photo_path' => 'beers/label.jpg']);
        $this->checkinWithPhoto($user, $beer, 'checkin-photos/one.jpg');

        Livewire::actingAs($user)
            ->test(BeerIndex::class)
            ->set('selected', [$beer->id])
            ->call('deleteSelected');

        $this->assertDatabaseCount('beers', 0);
        $this->assertDatabaseCount('checkin_photos', 0);
        Storage::disk('public')->assertMissing(['beers/label.jpg', 'checkin-photos/one.jpg']);
    }

    public function test_replacing_beer_photo_removes_old_file(): void
    {
        $user = User::factory()->create();
        Storage::disk('public')->put('beers/old.jpg', 'photo');
        $beer = Beer::create(['name' => 'Test IPA', 'photo_path' => 'beers/old.jpg']);

        Livewire::actingAs($user)
            ->test(BeerForm::class, ['beer' => $beer])
            ->set('photo', UploadedFile::fake()->image('new.jpg'))
            ->call('save');

        $newPath = $beer->fresh()->photo_path;
        $this->assertNotSame('beers/old.jpg', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('beers/old.jpg');
    }

    public function test_replacing_beer_photo_keeps_old_file_still_used_by_a_checkin(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Test IPA', 'photo_path' => 'checkin-photos/rss.jpg']);
        $this->checkinWithPhoto($user, $beer, 'checkin-photos/rss.jpg');

        Livewire::actingAs($user)
            ->test(BeerForm::class, ['beer' => $beer])
            ->set('photo', UploadedFile::fake()->image('new.jpg'))
            ->call('save');

        $this->assertNotSame('checkin-photos/rss.jpg', $beer->fresh()->photo_path);
        Storage::disk('public')->assertExists('checkin-photos/rss.jpg');
    }

    public function test_deleting_brewery_removes_its_logo_file(): void
    {
        $user = User::factory()->create();
        Storage::disk('public')->put('breweries/logo.png', 'logo');
        $brewery = Brewery::create(['name' => 'Test Brewery', 'logo_path' => 'breweries/logo.png']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $brewery])
            ->call('delete');

        $this->assertDatabaseMissing('breweries', ['id' => $brewery->id]);
        Storage::disk('public')->assertMissing('breweries/logo.png');
    }
}
