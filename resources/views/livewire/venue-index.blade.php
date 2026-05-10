<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Locations</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <x-pill-tabs
                :tabs="['venues' => 'Venues', 'breweries' => ['label' => 'Breweries', 'href' => route('locations.breweries')]]"
                active="venues"
            />

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 sm:ml-auto">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search venues..." class="flex-1 min-w-0" />
                <x-sort-control :options="['checkins' => 'Check-ins', 'name' => 'Name', 'recent' => 'Recent']" />

                {{-- Geocode button --}}
                @if($geocodingEnabled && $ungeocodedCount > 0)
                    <button
                        wire:click="geocodeVenues"
                        class="relative p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:text-amber-500 hover:border-amber-500 transition-colors flex-shrink-0"
                        title="Look up coordinates for {{ $ungeocodedCount }} {{ Str::plural('venue', $ungeocodedCount) }}"
                    >
                        <x-icon name="refresh" size="4" class="{{ $geocoding ? 'animate-spin' : '' }}" />
                        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-amber-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $ungeocodedCount }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Map --}}
    <div
        x-data="venueMap({{ $mapVenues->toJson() }})"
        x-init="init()"
        class="mb-6"
    >
        <div id="venue-map" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore aria-hidden="true" tabindex="-1"></div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex justify-end mb-4">
        <x-pill-tabs
            :tabs="['all' => 'All', 'missing' => ['label' => 'Missing Location', 'badge' => $ungeocodedCount ?: null], 'located' => 'With Location']"
            :active="$locationFilter"
            wireModel="locationFilter"
        />
    </div>

    {{-- Venue List (always visible) --}}
    @if($venues->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($venues as $venue)
                <a href="{{ route('venues.show', $venue) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <x-icon name="map-pin" size="5" class="text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $venue->name }}</h3>
                            @if($venue->displayLocation())
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $venue->displayLocation() }}</p>
                            @endif
                            @if($venue->address)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $venue->address }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if(!$venue->latitude || !$venue->longitude)
                                <span title="Missing location data" class="text-amber-500">
                                    <x-icon name="warning" size="4" />
                                </span>
                            @endif
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                {{ $venue->checkins_count }} {{ Str::plural('check-in', $venue->checkins_count) }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $venues->links() }}
        </div>
    @else
        <x-empty-state
            title="No venues yet"
            message="Venues will appear here when you check in at locations."
        />
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
