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
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 mb-6">
            <button
                wire:click="$set('location', '')"
                class="p-3 rounded-lg text-left transition-colors {{ $location === '' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 shadow-sm hover:shadow-md text-gray-900 dark:text-white' }}"
            >
                <p class="text-2xl font-bold">{{ $totalItems }}</p>
                <p class="text-xs {{ $location === '' ? 'text-amber-100' : 'text-gray-500 dark:text-gray-400' }}">All Locations</p>
                <p class="text-xs {{ $location === '' ? 'text-amber-200' : 'text-gray-400 dark:text-gray-500' }}">{{ $totalBeers }} {{ Str::plural('beer', $totalBeers) }}</p>
            </button>
            @foreach($locationSummary as $loc)
                <button
                    wire:click="$set('location', '{{ $loc->loc === 'Unassigned' ? '' : addslashes($loc->loc) }}')"
                    class="p-3 rounded-lg text-left transition-colors {{ $location === $loc->loc ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 shadow-sm hover:shadow-md text-gray-900 dark:text-white' }}"
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
        <div class="relative flex-1 min-w-0">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search inventory..." class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500" />
        </div>
        <x-sort-control :options="['recent' => 'Recent', 'name' => 'Name', 'quantity' => 'Quantity', 'acquired' => 'Acquired']" />
    </div>

    {{-- Inventory Grid --}}
    @if($items->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
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
                        class="absolute bottom-[3.25rem] right-1.5 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-all z-10"
                        title="Remove one"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
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
