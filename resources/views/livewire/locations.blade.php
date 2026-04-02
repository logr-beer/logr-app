<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Locations</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Nav --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <a href="{{ route('locations.venues') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">Venues</a>
                <span class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white">Breweries</span>
            </div>

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 sm:ml-auto">
                <div class="relative flex-1 min-w-0">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search breweries..."
                        class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
                    />
                </div>
                <x-sort-control :options="['checkins' => 'Beers', 'name' => 'Name', 'recent' => 'Recent']" />

                {{-- Geocode button --}}
                @if($geocodingEnabled && $ungeocodedCount > 0)
                    <button
                        wire:click="geocodeBreweries"
                        class="relative p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:text-amber-500 hover:border-amber-500 transition-colors flex-shrink-0"
                        title="Look up coordinates for {{ $ungeocodedCount }} {{ Str::plural('brewery', $ungeocodedCount) }}"
                    >
                        <svg class="w-4 h-4 {{ $geocoding ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182"/></svg>
                        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-amber-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $ungeocodedCount }}</span>
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
        <div id="breweries-map" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 z-0 relative"></div>
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
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
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
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
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
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
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
