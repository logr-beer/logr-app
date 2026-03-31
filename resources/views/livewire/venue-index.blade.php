<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Locations</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <a href="{{ route('locations', ['tab' => 'checkins']) }}" wire:navigate class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">Check-ins</a>
                <a href="{{ route('venues.index') }}" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white">Venues</a>
                <a href="{{ route('locations', ['tab' => 'breweries']) }}" wire:navigate class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">Breweries</a>
            </div>

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 flex-1 min-w-0 max-w-[50%]">
                <div class="relative flex-1 min-w-0">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search venues..."
                        class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
                    />
                </div>
                <select
                    wire:model.live="sortBy"
                    class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 flex-shrink-0"
                >
                    <option value="checkins">Most Check-ins</option>
                    <option value="name">Name</option>
                    <option value="recent">Recently Visited</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Map (always visible) --}}
    @if($mapVenues->count() > 0)
        <div
            x-data="venueMap({{ $mapVenues->toJson() }})"
            x-init="init()"
            class="mb-6"
        >
            <div id="venue-map" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore></div>
        </div>
    @endif

    {{-- Venue List (always visible) --}}
    @if($venues->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($venues as $venue)
                <a href="{{ route('venues.show', $venue) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $venue->name }}</h3>
                            @if($venue->displayLocation())
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $venue->displayLocation() }}</p>
                            @endif
                            @if($venue->address)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ $venue->address }}</p>
                            @endif
                        </div>
                        <span class="flex-shrink-0 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                            {{ $venue->checkins_count }} {{ Str::plural('check-in', $venue->checkins_count) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $venues->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No venues yet.</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Venues will appear here when you check in at locations.</p>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('venueMap', (venues) => ({
        map: null,
        init() {
            LeafletMap.loadLeaflet(() => this.renderMap(venues));
        },
        renderMap(venues) {
            this.map = LeafletMap.createMap('venue-map');
            if (!this.map) return;

            venues.forEach(v => {
                const popup = `<strong>${v.name}</strong>${v.location ? '<br><span style="color:#666;font-size:12px">' + v.location + '</span>' : ''}<br><span style="color:#999;font-size:11px">${v.checkins} check-in${v.checkins !== 1 ? 's' : ''}</span>`;
                LeafletMap.createMarker(this.map, v, { color: '#f59e0b', size: 12, popup });
            });

            LeafletMap.fitBounds(this.map, venues);
        }
    }));
</script>
@endscript
