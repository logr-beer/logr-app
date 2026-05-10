<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Beers</h1>
        <x-pill-tabs
            :tabs="['all' => ['label' => 'All Beers', 'href' => route('beers.index')], 'favorites' => ['label' => 'Favorites', 'href' => route('beers.index', ['filter' => 'favorites'])], 'inventory' => 'Inventory']"
            active="inventory"
        />
    </div>

    {{-- Location Summary Cards --}}
    @if($locationSummary->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-6">
            <button
                wire:click="$set('location', '')"
                class="p-3 rounded-lg text-left transition-colors {{ $location === '' ? 'bg-amber-600 text-white' : 'bg-white dark:bg-gray-800 shadow-sm hover:shadow-md text-gray-900 dark:text-white' }}"
            >
                <p class="text-2xl font-bold">{{ $totalItems }}</p>
                <p class="text-xs {{ $location === '' ? 'text-amber-100' : 'text-gray-500 dark:text-gray-400' }}">All Locations</p>
                <p class="text-xs {{ $location === '' ? 'text-amber-200' : 'text-gray-400 dark:text-gray-500' }}">{{ $totalBeers }} {{ Str::plural('beer', $totalBeers) }}</p>
            </button>
            @foreach($locationSummary as $loc)
                <button
                    wire:click="$set('location', '{{ $loc->loc === 'Unassigned' ? '' : addslashes($loc->loc) }}')"
                    class="p-3 rounded-lg text-left transition-colors {{ $location === $loc->loc ? 'bg-amber-600 text-white' : 'bg-white dark:bg-gray-800 shadow-sm hover:shadow-md text-gray-900 dark:text-white' }}"
                >
                    <p class="text-2xl font-bold">{{ $loc->total }}</p>
                    <p class="text-xs {{ $location === $loc->loc ? 'text-amber-100' : 'text-gray-500 dark:text-gray-400' }} truncate">{{ $loc->loc }}</p>
                    <p class="text-xs {{ $location === $loc->loc ? 'text-amber-200' : 'text-gray-400 dark:text-gray-500' }}">{{ $loc->unique_beers }} {{ Str::plural('beer', $loc->unique_beers) }}</p>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Search & Sort --}}
    <div class="flex gap-3 mb-6">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search inventory..." class="flex-1 min-w-0" />
        <x-sort-control :options="['recent' => 'Recent', 'name' => 'Name', 'quantity' => 'Quantity', 'acquired' => 'Acquired']" />
    </div>

    {{-- Inventory Grid --}}
    @if($items->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($items as $item)
                @php
                    $itemBadges = [];
                    if ($item->is_gift) {
                        $itemBadges[] = ['label' => 'Gift', 'position' => 'left', 'style' => 'pink'];
                    }
                    if ($item->beer->abv) {
                        $itemBadges[] = ['label' => $item->beer->abv . '%', 'position' => 'left', 'style' => 'dark', 'icon' => 'flask'];
                    }
                    $itemBadges[] = ['label' => '×' . $item->quantity, 'position' => 'right', 'style' => 'dark'];
                @endphp
                <div class="relative">
                    <x-beer-card
                        :beer="$item->beer"
                        :badges="$itemBadges"
                        :subtitle="$item->storage_location"
                        :date="$item->date_acquired"
                        dateLabel="Acquired"
                    />
                    {{-- Decrement button --}}
                    <button
                        wire:click="removeItem({{ $item->id }})"
                        class="absolute bottom-[3.25rem] right-1.5 p-1.5 rounded-full bg-gray-900/70 text-white opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-all z-10"
                        title="Remove one"
                    >
                        <x-icon name="minus" size="3" />
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16">
            <x-icon name="archive-box" size="16" class="text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No inventory</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                @if($search || $location)
                    Try adjusting your search or filters.
                @else
                    Add beers to your inventory from the beer detail page.
                @endif
            </p>
        </div>
    @endif
</div>
