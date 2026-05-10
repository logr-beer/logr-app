<div>
    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <x-stat :value="$stats['total_checkins']" label="Check-ins" :href="route('checkins.index')" />
        <x-stat :value="$stats['library_count']" label="In Library" :href="route('beers.index')" />
        <x-stat :value="$stats['in_fridge']" label="In Stock" :href="route('beers.inventory')" />
        <x-stat :value="$stats['avg_rating'] ? number_format($stats['avg_rating'], 2) . ' ★' : '—'" label="Avg Rating" :href="route('stats')" />
    </div>

    {{-- Recently Added --}}
    <x-beer-row title="Recently Added" :beers="$recentBeers" />

    {{-- Recently Checked In --}}
    <x-beer-row title="Recently Checked In" :beers="$recentCheckins" dateField="last_checkin_at" dateLabel="Checked in" />

    {{-- Favorites --}}
    <x-beer-row title="Favorites" :beers="$favorites" />

    {{-- Year Collections --}}
    @if($yearCollections->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">By Year</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($yearCollections as $collection)
                @include('livewire.partials.collection-card', ['collection' => $collection])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Location Collections --}}
    @if($locationCollections->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">By Location</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($locationCollections as $collection)
                @include('livewire.partials.collection-card', ['collection' => $collection])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state / Getting started --}}
    @if($recentBeers->isEmpty() && $favorites->isEmpty())
    <div class="max-w-lg mx-auto py-12">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Welcome to Logr</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">A place to keep track of what's in your beer fridge. Here are a few things to get started:</p>

        <ul class="space-y-3 mb-8">
            <li class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <span class="mt-0.5 text-amber-500">
                    <x-icon name="plus" size="5" />
                </span>
                <div>
                    <a href="{{ route('beers.create') }}" wire:navigate class="font-semibold text-gray-900 dark:text-white hover:text-amber-500 transition-colors">Add your first beer</a>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Start building your library by logging a beer.</p>
                </div>
            </li>
            <li class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <span class="mt-0.5 text-amber-500">
                    <x-icon name="upload" size="5" />
                </span>
                <div>
                    <a href="{{ route('import') }}" wire:navigate class="font-semibold text-gray-900 dark:text-white hover:text-amber-500 transition-colors">Import from CSV</a>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bring in your data from Untappd or another source.</p>
                </div>
            </li>
            <li class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <span class="mt-0.5 text-amber-500">
                    <x-icon name="flask" size="5" />
                </span>
                <div>
                    <button wire:click="loadDemoData" wire:confirm="This will populate your account with example beers, check-ins, and collections. Continue?" class="font-semibold text-gray-900 dark:text-white hover:text-amber-500 transition-colors text-left">Load example data</button>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Explore Logr with sample beers, check-ins, and collections.</p>
                </div>
            </li>
        </ul>
    </div>
    @endif
</div>
