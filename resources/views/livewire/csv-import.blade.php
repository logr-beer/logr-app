<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Import from CSV</h2>

    {{-- Step 1: Upload --}}
    @if ($step === 'upload')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Import Type</label>
                <select wire:model.live="importType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm">
                    <option value="checkins">Check-ins Only</option>
                    <option value="inventory">Inventory Only</option>
                    <option value="both">Check-ins + Inventory</option>
                </select>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if ($importType === 'checkins')
                        Import beer check-in history (ratings, dates, venues, notes).
                    @elseif ($importType === 'inventory')
                        Import beer inventory (quantities, storage locations).
                    @else
                        Import both check-ins and inventory data from a single CSV.
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV File</label>
                <input type="file" wire:model="csvFile" accept=".csv,.txt"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                        file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-amber-50 file:text-amber-700 dark:file:bg-amber-900 dark:file:text-amber-200
                        hover:file:bg-amber-100 dark:hover:file:bg-amber-800" />
                @error('csvFile') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                <div wire:loading wire:target="csvFile" class="text-sm text-gray-500 dark:text-gray-400 mt-2">Reading file...</div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Supported Formats</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li>Untappd export CSV (auto-detected)</li>
                    <li>Any CSV with a header row — you'll map columns in the next step</li>
                    <li>Beers not found locally will be created automatically</li>
                </ul>
            </div>
        </div>
    @endif

    {{-- Step 2: Column Mapping --}}
    @if ($step === 'map')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Map Columns</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $totalRows }} rows found. Map your CSV columns to the fields below.</p>
                </div>
                <button wire:click="backToUpload" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &larr; Back
                </button>
            </div>

            @error('mapping') <div class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-md p-3 text-sm">{{ $message }}</div> @enderror

            <div class="space-y-3">
                @foreach ($csvHeaders as $header)
                    <div class="flex items-center gap-4">
                        <div class="w-1/3">
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $header }}</span>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        <div class="w-1/2">
                            <select wire:model="mapping.{{ $header }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm">
                                @foreach ($availableFields as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Preview of first few rows --}}
            @if (count($csvPreview) > 0)
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Preview (first {{ count($csvPreview) }} rows)</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr>
                                    @foreach ($csvHeaders as $header)
                                        <th class="px-2 py-1 text-left text-gray-600 dark:text-gray-400 border-b dark:border-gray-600">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($csvPreview as $row)
                                    <tr>
                                        @foreach ($csvHeaders as $header)
                                            <td class="px-2 py-1 text-gray-700 dark:text-gray-300 border-b dark:border-gray-700 max-w-[200px] truncate">{{ $row[$header] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <button wire:click="startPreview" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-md transition">
                    Continue
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: Confirm & Import --}}
    @if ($step === 'preview')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Confirm Import</h3>
                <button wire:click="backToMap" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &larr; Back
                </button>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4 space-y-2">
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Import type:</strong>
                    @if ($importType === 'checkins') Check-ins
                    @elseif ($importType === 'inventory') Inventory
                    @else Check-ins + Inventory
                    @endif
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Rows to import:</strong> {{ $totalRows }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Mapped columns:</strong></p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-2">
                    @foreach ($mapping as $csvHeader => $field)
                        @if ($field)
                            <li>{{ $csvHeader }} &rarr; {{ $availableFields[$field] ?? $field }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-md p-4">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    Beers and breweries not found in your local database will be created automatically.
                    Duplicate Untappd check-ins (by ID) will be skipped.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <button wire:click="backToMap" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                    Go Back
                </button>
                <button wire:click="runImport" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-md transition">
                    Import {{ $totalRows }} Rows
                </button>
            </div>
        </div>
    @endif

    {{-- Step 4: Importing --}}
    @if ($step === 'importing')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto"></div>
            <p class="text-gray-700 dark:text-gray-300">Importing... {{ $processedRows }} / {{ $totalRows }}</p>
        </div>
    @endif

    {{-- Step 5: Results --}}
    @if ($step === 'results')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-semibold text-green-600 dark:text-green-400">Import Complete</h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @if (in_array($importType, ['checkins', 'both']))
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-md p-4 text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $results['checkins'] }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300">Check-ins</div>
                    </div>
                @endif
                @if (in_array($importType, ['inventory', 'both']))
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $results['inventory'] }}</div>
                        <div class="text-sm text-blue-700 dark:text-blue-300">Inventory</div>
                    </div>
                @endif
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-md p-4 text-center">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $results['created_beers'] }}</div>
                    <div class="text-sm text-amber-700 dark:text-amber-300">New Beers</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4 text-center">
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $results['skipped'] }}</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">Skipped</div>
                </div>
            </div>

            @if (count($importErrors) > 0)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-md p-4">
                    <h4 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2">Errors ({{ count($importErrors) }})</h4>
                    <ul class="text-sm text-red-600 dark:text-red-400 space-y-1 max-h-40 overflow-y-auto">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="resetImport" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-md transition">
                    Import Another File
                </button>
                <a href="{{ route('beers.index') }}" wire:navigate class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                    View Beers
                </a>
                @if (in_array($importType, ['checkins', 'both']))
                    <a href="{{ route('checkins.index') }}" wire:navigate class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                        View Check-ins
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
