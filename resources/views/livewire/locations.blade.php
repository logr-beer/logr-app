<div>
    {{-- Header --}}
    <div class="mb-6">
        <x-page-header title="Locations" />
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <x-pill-tabs
                :tabs="['venues' => ['label' => 'Venues', 'href' => route('locations.venues')], 'breweries' => 'Breweries']"
                active="breweries"
            />

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 sm:ml-auto">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search breweries..." class="flex-1 min-w-0" />
                <x-sort-control :options="['checkins' => 'Beers', 'name' => 'Name', 'recent' => 'Recent']" />

                {{-- Geocode button --}}
                @if($geocodingEnabled && $ungeocodedCount > 0)
                    <button
                        wire:click="geocodeBreweries"
                        class="relative p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:text-amber-500 hover:border-amber-500 transition-colors flex-shrink-0"
                        title="Look up coordinates for {{ $ungeocodedCount }} {{ Str::plural('brewery', $ungeocodedCount) }}"
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
        x-data="locationMap({{ $mapPoints->values()->toJson() }})"
        x-init="init()"
        class="mb-6"
    >
        <div id="breweries-map" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 z-0 relative" aria-hidden="true" tabindex="-1"></div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex justify-end mb-4">
        <x-pill-tabs
            :tabs="['all' => 'All', 'missing' => ['label' => 'Missing Location', 'badge' => $ungeocodedCount ?: null], 'located' => 'With Location']"
            :active="$locationFilter"
            wireModel="locationFilter"
        />
    </div>

    {{-- List --}}
    @if($listItems->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($listItems as $item)
                <div
                    @if($item->latitude && $item->longitude)
                        onclick="if(window._locMap){window._locMap.setView([{{ $item->latitude }},{{ $item->longitude }}],14);window.scrollTo({top:0,behavior:'smooth'})}"
                    @endif
                    class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow {{ $item->latitude && $item->longitude ? 'cursor-pointer' : '' }}"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            @if($item->logo_path)
                                <img src="{{ Storage::url($item->logo_path) }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover">
                            @else
                                <x-icon name="building" size="5" class="text-amber-600 dark:text-amber-400" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h3>
                            @php $loc = collect([$item->city, $item->state, $item->country])->filter()->implode(', '); @endphp
                            @if($loc)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $loc }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if(!$item->latitude || !$item->longitude)
                                <span title="Missing location data" class="text-amber-500">
                                    <x-icon name="warning" size="4" />
                                </span>
                            @endif
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                {{ $item->beers_count }} {{ Str::plural('beer', $item->beers_count) }}
                            </span>
                            <a
                                href="{{ route('beers.index', ['search' => $item->name]) }}"
                                wire:navigate
                                onclick="event.stopPropagation()"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all opacity-0 group-hover:opacity-100"
                                title="View beers by {{ $item->name }}"
                            >
                                <x-icon name="external-link" size="4" />
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $listItems->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <x-icon name="map-pin" size="16" class="text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400 text-lg">No breweries found.</p>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('locationMap', (points) => ({
        map: null,
        init() {
            LeafletMap.loadLeaflet(() => this.renderMap(points));
        },
        renderMap(points) {
            this.map = LeafletMap.createMap('breweries-map');
            if (!this.map) return;
            window._locMap = this.map;

            points.forEach(p => {
                const size = Math.min(24, Math.max(10, 8 + (p.beers || 0) * 3));
                const label = size > 14 ? (p.beers || 0) : '';

                const beerLinks = (p.beerList || []).map(b =>
                    `<a href="/beers/${b.id}" style="color:#d97706;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${b.name}</a>`
                ).join('<br>');
                const moreText = p.hasMore ? `<br><span style="color:#999;font-size:11px">+ ${p.beers - 5} more</span>` : '';
                const popup = `<div style="min-width:180px;max-width:250px"><strong style="font-size:14px">${p.name}</strong>${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}<div style="margin-top:6px;font-size:12px;color:#888">${p.beers} ${p.beers === 1 ? 'beer' : 'beers'} &middot; ${p.checkins} ${p.checkins === 1 ? 'check-in' : 'check-ins'}</div>${beerLinks ? '<div style="margin-top:6px;font-size:12px;line-height:1.6">' + beerLinks + moreText + '</div>' : ''}</div>`;

                LeafletMap.createMarker(this.map, p, { color: '#f59e0b', size, label, popup });
            });

            LeafletMap.fitBounds(this.map, points);
        }
    }));
</script>
@endscript
