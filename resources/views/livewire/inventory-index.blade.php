<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Beers</h1>
            <div class="flex items-center gap-1 mt-2">
                <a href="{{ route('beers.index') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">Library</a>
                <a href="{{ route('beers.inventory') }}" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white">Inventory</a>
            </div>
        </div>
    </div>

    {{-- Location Summary Cards --}}
    @if($locationSummary->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-6">
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
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($items as $item)
                <div class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <a href="{{ route('beers.show', $item->beer) }}" wire:navigate class="block">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
                            @if($item->beer->photo_path)
                                <img src="{{ Storage::url($item->beer->photo_path) }}" alt="{{ $item->beer->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <x-application-logo class="w-8 h-8 stroke-current" />
                                </div>
                            @endif

                            {{-- Quantity badge --}}
                            <div class="absolute top-1.5 right-1.5 bg-black/70 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                &times;{{ $item->quantity }}
                            </div>

                            {{-- Gift badge --}}
                            @if($item->is_gift)
                                <div class="absolute top-1.5 left-1.5 bg-pink-500/80 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                    Gift
                                </div>
                            @endif
                        </div>
                    </a>

                    {{-- Decrement button --}}
                    <button
                        wire:click="removeItem({{ $item->id }})"
                        class="absolute bottom-[3.25rem] right-1.5 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-all"
                        title="Remove one"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>

                    <div class="p-2">
                        <h3 class="font-semibold text-xs text-gray-900 dark:text-white truncate">{{ $item->beer->name }}</h3>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $item->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            @if($item->storage_location)
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 truncate">{{ $item->storage_location }}</span>
                            @endif
                            @if($item->date_acquired)
                                <span class="text-[10px] text-gray-400">{{ $item->date_acquired->format('M j') }}</span>
                            @endif
                        </div>
                    </div>
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
