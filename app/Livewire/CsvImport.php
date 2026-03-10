<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Venue;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class CsvImport extends Component
{
    use WithFileUploads;

    public $csvFile;
    public string $importType = 'checkins'; // checkins, inventory, both
    public array $csvHeaders = [];
    public array $csvPreview = [];
    public array $mapping = [];
    public int $totalRows = 0;
    public string $step = 'upload'; // upload, map, preview, importing, results
    public array $results = [];
    public int $processedRows = 0;
    public array $importErrors = [];
    public array $rawData = [];

    // All mappable fields
    public function getCheckinFields(): array
    {
        return [
            '' => '— Skip —',
            'beer_name' => 'Beer Name',
            'brewery_name' => 'Brewery Name',
            'brewery_city' => 'Brewery City',
            'brewery_state' => 'Brewery State',
            'brewery_country' => 'Brewery Country',
            'beer_style' => 'Beer Style',
            'beer_abv' => 'ABV',
            'beer_ibu' => 'IBU',
            'rating' => 'Rating',
            'serving_type' => 'Serving Type',
            'notes' => 'Notes / Comment',
            'venue_name' => 'Venue Name',
            'venue_city' => 'Venue City',
            'venue_state' => 'Venue State',
            'venue_country' => 'Venue Country',
            'checkin_date' => 'Date',
            'untappd_id' => 'Untappd Checkin ID',
        ];
    }

    public function getInventoryFields(): array
    {
        return [
            '' => '— Skip —',
            'beer_name' => 'Beer Name',
            'brewery_name' => 'Brewery Name',
            'brewery_city' => 'Brewery City',
            'brewery_state' => 'Brewery State',
            'brewery_country' => 'Brewery Country',
            'beer_style' => 'Beer Style',
            'beer_abv' => 'ABV',
            'beer_ibu' => 'IBU',
            'quantity' => 'Quantity',
            'storage_location' => 'Storage Location',
            'purchase_location' => 'Purchase Location',
            'date_acquired' => 'Date Acquired',
            'inventory_notes' => 'Notes',
        ];
    }

    public function getAvailableFields(): array
    {
        if ($this->importType === 'inventory') {
            return $this->getInventoryFields();
        }
        if ($this->importType === 'both') {
            return array_merge($this->getCheckinFields(), [
                'quantity' => 'Quantity (Inventory)',
                'storage_location' => 'Storage Location',
                'purchase_location' => 'Purchase Location',
                'date_acquired' => 'Date Acquired',
                'inventory_notes' => 'Inventory Notes',
            ]);
        }
        return $this->getCheckinFields();
    }

    public function updatedCsvFile(): void
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $this->parseCsv();
    }

    protected function parseCsv(): void
    {
        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->addError('csvFile', 'Could not read the CSV file.');
            return;
        }

        // Read headers
        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            $this->addError('csvFile', 'CSV file appears to be empty.');
            return;
        }

        // Clean BOM and whitespace from headers
        $this->csvHeaders = array_map(fn ($h) => trim(str_replace("\xEF\xBB\xBF", '', $h)), $headers);

        // Read all data rows
        $this->rawData = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($this->csvHeaders)) {
                $this->rawData[] = array_combine($this->csvHeaders, $row);
            }
        }
        fclose($handle);

        $this->totalRows = count($this->rawData);
        $this->csvPreview = array_slice($this->rawData, 0, 5);

        // Auto-map columns based on common header names
        $this->autoMapColumns();

        $this->step = 'map';
    }

    protected function autoMapColumns(): void
    {
        $headerMap = [
            // Untappd export format
            'beer_name' => ['beer_name', 'beer name', 'beer'],
            'brewery_name' => ['brewery_name', 'brewery name', 'brewery'],
            'brewery_city' => ['brewery_city', 'brewery city'],
            'brewery_state' => ['brewery_state', 'brewery state'],
            'brewery_country' => ['brewery_country', 'brewery country'],
            'beer_style' => ['beer_type', 'beer type', 'beer_style', 'beer style', 'style', 'type'],
            'beer_abv' => ['beer_abv', 'beer abv', 'abv'],
            'beer_ibu' => ['beer_ibu', 'beer ibu', 'ibu'],
            'rating' => ['rating_score', 'rating score', 'rating', 'score'],
            'serving_type' => ['serving_type', 'serving type', 'serving'],
            'notes' => ['comment', 'notes', 'checkin_comment', 'review'],
            'venue_name' => ['venue_name', 'venue name', 'venue', 'location'],
            'venue_city' => ['venue_city', 'venue city'],
            'venue_state' => ['venue_state', 'venue state'],
            'venue_country' => ['venue_country', 'venue country'],
            'checkin_date' => ['created_at', 'date', 'checkin_date', 'check-in date', 'checkin date', 'created'],
            'untappd_id' => ['checkin_id', 'untappd_id', 'checkin id'],
            'quantity' => ['quantity', 'qty', 'count'],
            'storage_location' => ['storage_location', 'storage location', 'storage', 'location'],
            'purchase_location' => ['purchase_location', 'purchase location', 'purchased_at', 'store'],
            'date_acquired' => ['date_acquired', 'date acquired', 'purchase_date', 'purchase date', 'acquired'],
            'inventory_notes' => ['inventory_notes', 'inventory notes'],
        ];

        $this->mapping = [];
        foreach ($this->csvHeaders as $csvHeader) {
            $normalized = strtolower(trim($csvHeader));
            $matched = '';
            foreach ($headerMap as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $matched = $field;
                    break;
                }
            }
            $this->mapping[$csvHeader] = $matched;
        }
    }

    public function startPreview(): void
    {
        // Validate that at least beer_name is mapped
        $mappedFields = array_filter($this->mapping);
        if (! in_array('beer_name', $mappedFields)) {
            $this->addError('mapping', 'You must map at least the Beer Name column.');
            return;
        }

        $this->step = 'preview';
    }

    public function backToMap(): void
    {
        $this->step = 'map';
    }

    public function backToUpload(): void
    {
        $this->step = 'upload';
        $this->reset(['csvHeaders', 'csvPreview', 'mapping', 'totalRows', 'rawData', 'csvFile']);
    }

    public function runImport(): void
    {
        $this->step = 'importing';
        $this->results = ['created_beers' => 0, 'existing_beers' => 0, 'checkins' => 0, 'inventory' => 0, 'skipped' => 0];
        $this->importErrors = [];
        $this->processedRows = 0;

        // Build reverse mapping: field => csv header
        $fieldMap = [];
        foreach ($this->mapping as $csvHeader => $field) {
            if ($field) {
                $fieldMap[$field] = $csvHeader;
            }
        }

        foreach ($this->rawData as $index => $row) {
            try {
                $this->importRow($row, $fieldMap, $index + 2); // +2 for 1-indexed + header row
            } catch (\Throwable $e) {
                $this->importErrors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
            $this->processedRows++;
        }

        $this->step = 'results';
    }

    protected function importRow(array $row, array $fieldMap, int $rowNum): void
    {
        $getValue = fn (string $field) => isset($fieldMap[$field]) ? trim($row[$fieldMap[$field]] ?? '') : '';

        $beerName = $getValue('beer_name');
        if (! $beerName) {
            $this->results['skipped']++;
            return;
        }

        // Find or create brewery
        $brewery = null;
        $breweryName = $getValue('brewery_name');
        if ($breweryName) {
            $brewery = Brewery::where('name', $breweryName)->first();
            if (! $brewery) {
                $brewery = Brewery::create([
                    'name' => $breweryName,
                    'city' => $getValue('brewery_city') ?: null,
                    'state' => $getValue('brewery_state') ?: null,
                    'country' => $getValue('brewery_country') ?: null,
                ]);
            }
        }

        // Find or create beer
        $beerQuery = Beer::where('name', $beerName);
        if ($brewery) {
            $beerQuery->where('brewery_id', $brewery->id);
        }
        $beer = $beerQuery->first();

        if ($beer) {
            $this->results['existing_beers']++;
            // Backfill missing fields
            $updates = [];
            if (! $beer->brewery_id && $brewery) $updates['brewery_id'] = $brewery->id;
            if (! $beer->abv && ($abv = $getValue('beer_abv'))) $updates['abv'] = (float) $abv;
            if (! $beer->ibu && ($ibu = $getValue('beer_ibu'))) $updates['ibu'] = (float) $ibu;
            if (empty($beer->style) && ($style = $getValue('beer_style'))) $updates['style'] = $this->parseStyles($style);
            if ($updates) $beer->update($updates);
        } else {
            $style = $getValue('beer_style');
            $beer = Beer::create([
                'name' => $beerName,
                'brewery_id' => $brewery?->id,
                'style' => $style ? $this->parseStyles($style) : [],
                'abv' => ($abv = $getValue('beer_abv')) ? (float) $abv : null,
                'ibu' => ($ibu = $getValue('beer_ibu')) ? (float) $ibu : null,
            ]);
            $this->results['created_beers']++;
        }

        // Import checkin
        if (in_array($this->importType, ['checkins', 'both'])) {
            $rating = $getValue('rating');
            $checkinDate = $getValue('checkin_date');
            $untappdId = $getValue('untappd_id');

            // Skip duplicate Untappd checkins
            if ($untappdId && Checkin::where('untappd_id', $untappdId)->exists()) {
                $this->results['skipped']++;
                if ($this->importType === 'checkins') return;
            } else {
                // Resolve venue
                $venueId = null;
                $venueName = $getValue('venue_name');
                if ($venueName) {
                    $venue = Venue::where('name', $venueName)->first();
                    if (! $venue) {
                        $venue = Venue::create([
                            'name' => $venueName,
                            'city' => $getValue('venue_city') ?: null,
                            'state' => $getValue('venue_state') ?: null,
                            'country' => $getValue('venue_country') ?: null,
                        ]);
                    }
                    $venueId = $venue->id;
                }

                // Normalize serving type
                $servingType = strtolower($getValue('serving_type'));
                $validTypes = ['draft', 'bottle', 'can', 'crowler', 'growler', 'cask'];
                if (! in_array($servingType, $validTypes)) {
                    $servingType = null;
                }

                $checkinData = [
                    'user_id' => auth()->id(),
                    'beer_id' => $beer->id,
                    'rating' => $rating !== '' ? (float) $rating : null,
                    'serving_type' => $servingType,
                    'venue_id' => $venueId,
                    'location' => $venueName ?: null,
                    'notes' => $getValue('notes') ?: null,
                    'untappd_id' => $untappdId ?: null,
                ];

                $checkin = Checkin::create($checkinData);

                // Set the created_at date if provided
                if ($checkinDate) {
                    try {
                        $parsed = Carbon::parse($checkinDate);
                        $checkin->update(['created_at' => $parsed, 'updated_at' => $parsed]);
                    } catch (\Throwable $e) {
                        // Ignore unparseable dates, keep current timestamp
                    }
                }

                $this->results['checkins']++;
            }
        }

        // Import inventory
        if (in_array($this->importType, ['inventory', 'both'])) {
            $quantity = $getValue('quantity');
            $storageLocation = $getValue('storage_location');
            $dateAcquired = $getValue('date_acquired');

            // Default quantity to 1 if doing inventory import
            $qty = $quantity !== '' ? (int) $quantity : 1;
            if ($qty <= 0) $qty = 1;

            $inventoryData = [
                'beer_id' => $beer->id,
                'user_id' => auth()->id(),
                'quantity' => $qty,
                'storage_location' => $storageLocation ?: null,
                'purchase_location' => $getValue('purchase_location') ?: null,
                'notes' => $getValue('inventory_notes') ?: null,
            ];

            if ($dateAcquired) {
                try {
                    $inventoryData['date_acquired'] = Carbon::parse($dateAcquired)->toDateString();
                } catch (\Throwable $e) {
                    // skip bad date
                }
            }

            // Check for existing inventory with same beer + storage location
            $existing = Inventory::where('beer_id', $beer->id)
                ->where('user_id', auth()->id())
                ->where('storage_location', $inventoryData['storage_location'])
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $qty]);
            } else {
                Inventory::create($inventoryData);
            }

            $this->results['inventory']++;
        }
    }

    protected function parseStyles(string $raw): array
    {
        // Split on comma, slash, or pipe — common CSV delimiters for multi-value fields
        $styles = preg_split('/[,\/|]+/', $raw);

        return array_values(array_filter(array_map('trim', $styles)));
    }

    public function resetImport(): void
    {
        $this->reset();
    }

    public function render()
    {
        return view('livewire.csv-import', [
            'availableFields' => $this->getAvailableFields(),
        ])->title('Import');
    }
}
