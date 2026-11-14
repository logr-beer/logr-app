<?php

namespace Tests\Feature;

use App\Concerns\StoresPhotos;
use App\Livewire\BeerForm;
use App\Livewire\BeerShow;
use App\Livewire\CheckinForm;
use App\Models\Beer;
use App\Models\CheckinPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CheckinPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * A plain-text file named like a photo. Text is allowed through the
     * temporary upload rules (CSV/JSON imports), so the component has to reject it.
     */
    private function nonImage(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pour.jpg', 'not a photo')->mimeType('text/plain');
    }

    public function test_beer_form_rejects_non_image_checkin_photo(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Upload IPA')
            ->set('addCheckin', true)
            ->set('checkinPhotos', [$this->nonImage()])
            ->call('save')
            ->assertHasErrors('checkinPhotos.0');

        $this->assertDatabaseMissing('beers', ['name' => 'Upload IPA']);
        $this->assertSame(0, CheckinPhoto::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_temporary_upload_rejects_disallowed_file_types(): void
    {
        $user = User::factory()->create();
        $script = UploadedFile::fake()->createWithContent('pour.jpg', '<?php echo "hello";')->mimeType('text/x-php');

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('addCheckin', true)
            ->set('checkinPhotos', [$script])
            ->assertHasErrors('checkinPhotos.0')
            ->assertSet('checkinPhotos', []);

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_beer_form_stores_valid_checkin_photo_with_image_extension(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Photo IPA')
            ->set('addCheckin', true)
            ->set('checkinPhotos', [UploadedFile::fake()->image('pour.png')])
            ->call('save')
            ->assertHasNoErrors();

        $photo = CheckinPhoto::sole();
        $this->assertMatchesRegularExpression('#^checkin-photos/[A-Za-z0-9]{40}\.png$#', $photo->photo_path);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_beer_form_limits_number_of_checkin_photos(): void
    {
        $user = User::factory()->create();

        $photos = array_map(fn ($i) => UploadedFile::fake()->image("pour{$i}.jpg"), range(1, 11));

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Many Photos IPA')
            ->set('addCheckin', true)
            ->set('checkinPhotos', $photos)
            ->call('save')
            ->assertHasErrors('checkinPhotos');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_beer_photo_is_stored_with_image_extension(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Label IPA')
            ->set('photo', UploadedFile::fake()->image('label.jpeg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertMatchesRegularExpression('#^beers/[A-Za-z0-9]{40}\.jpg$#', Beer::sole()->photo_path);
    }

    public function test_beer_show_checkin_rejects_non_image_and_stores_image_safely(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Show IPA']);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('checkinPhotos', [$this->nonImage()])
            ->call('submitCheckin')
            ->assertHasErrors('checkinPhotos.0');

        $this->assertEmpty(Storage::disk('public')->allFiles());

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('checkinPhotos', [UploadedFile::fake()->image('pour.webp')])
            ->call('submitCheckin')
            ->assertHasNoErrors();

        $this->assertMatchesRegularExpression('#^checkin-photos/[A-Za-z0-9]{40}\.webp$#', CheckinPhoto::sole()->photo_path);
    }

    public function test_checkin_form_rejects_non_image_and_stores_image_safely(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Form IPA']);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('photos', [$this->nonImage()])
            ->call('submitCheckin')
            ->assertHasErrors('photos.0');

        $this->assertEmpty(Storage::disk('public')->allFiles());

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('photos', [UploadedFile::fake()->image('pour.gif')])
            ->call('submitCheckin')
            ->assertHasNoErrors();

        $this->assertMatchesRegularExpression('#^checkin-photos/[A-Za-z0-9]{40}\.gif$#', CheckinPhoto::sole()->photo_path);
    }

    public function test_store_photo_refuses_types_outside_the_allow_list(): void
    {
        $storer = new class
        {
            use StoresPhotos;

            public function store(UploadedFile $photo): string
            {
                return $this->storePhoto($photo, 'checkin-photos');
            }
        };

        $this->expectException(\InvalidArgumentException::class);

        try {
            $storer->store(UploadedFile::fake()->create('pour.bmp', 1, 'image/bmp'));
        } finally {
            $this->assertEmpty(Storage::disk('public')->allFiles());
        }
    }
}
