<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Restore from Backup</h2>

    {{-- Step 1: Upload --}}
    @if ($step === 'upload')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logr Backup File</label>
                <input type="file" wire:model="jsonFile" accept=".json"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                        file:text-sm file:font-medium
                        file:bg-amber-600 file:text-white
                        hover:file:bg-amber-700" />
                @error('jsonFile') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                <div wire:loading wire:target="jsonFile" class="text-sm text-gray-500 dark:text-gray-400 mt-2">Reading file...</div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">About Backup Import</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li>Import a <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-600 rounded text-xs font-mono">logr-export-*.json</code> file exported from Logr</li>
                    <li>Existing records are matched and skipped &mdash; no duplicates</li>
                    <li>Missing data on existing records is backfilled automatically</li>
                </ul>
            </div>
        </div>
    @endif

    {{-- Step 2: Preview --}}
    @if ($step === 'preview')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Confirm Import</h3>
                <button wire:click="backToUpload" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &larr; Back
                </button>
            </div>

            @if ($summary['exported_at'])
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Exported {{ \Carbon\Carbon::parse($summary['exported_at'])->diffForHumans() }}
                    (v{{ $summary['version'] }})
                </p>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach (['breweries', 'beers', 'checkins', 'inventory', 'venues', 'stores', 'collections', 'tags', 'companions'] as $section)
                    @if ($summary[$section] > 0)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-3 text-center">
                            <div class="text-xl font-bold text-gray-700 dark:text-gray-300">{{ $summary[$section] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $section }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-md p-4">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    Records are matched by UUID or name. Existing records will be kept &mdash; only missing data is backfilled. Duplicate check-ins are skipped.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <button wire:click="backToUpload" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                    Go Back
                </button>
                <x-primary-button type="button" wire:click="runImport">Import All Data</x-primary-button>
            </div>
        </div>
    @endif

    {{-- Step 3: Importing --}}
    @if ($step === 'importing')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto"></div>
            <p class="text-gray-700 dark:text-gray-300">Importing your data...</p>
        </div>
    @endif

    {{-- Step 4: Results --}}
    @if ($step === 'results')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-semibold text-green-600 dark:text-green-400">Import Complete</h3>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($results as $section => $counts)
                    @php
                        $total = array_sum($counts);
                    @endphp
                    @if ($total > 0)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-3">
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize mb-1">{{ $section }}</div>
                            @foreach ($counts as $label => $count)
                                @if ($count > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400 capitalize">{{ $label }}</span>
                                        <span class="font-medium {{ $label === 'created' ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">{{ $count }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
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
                <x-primary-button type="button" wire:click="resetImport">Import Another File</x-primary-button>
                <a href="{{ route('beers.index') }}" wire:navigate class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                    View Beers
                </a>
                <a href="{{ route('checkins.index') }}" wire:navigate class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-md transition">
                    View Check-ins
                </a>
            </div>
        </div>
    @endif
</div>
