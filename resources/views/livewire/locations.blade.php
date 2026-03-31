<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Locations</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <button
                    wire:click="$set('tab', 'checkins')"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $tab === 'checkins' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    Check-ins
                </button>
                <a
                    href="{{ route('venues.index') }}"
                    wire:navigate
                    class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    Venues
                </a>
                <button
                    wire:click="$set('tab', 'breweries')"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $tab === 'breweries' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    Breweries
                </button>
            </div>

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 flex-1 min-w-0 max-w-[50%]">
                <div class="relative flex-1 min-w-0">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search {{ $tab === 'breweries' ? 'breweries' : 'venues' }}..."
                        class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
                    />
                </div>
                <select
                    wire:model.live="sortBy"
                    class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 flex-shrink-0"
                >
                    <option value="checkins">Most {{ $tab === 'breweries' ? 'Beers' : 'Check-ins' }}</option>
                    <option value="name">Name</option>
                    <option value="recent">Recent</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Map --}}
    @if($mapPoints->count() > 0)
        <div
            x-data="locationMap({{ $mapPoints->values()->toJson() }}, '{{ $tab }}')"
            x-init="init()"
            class="mb-6"
            x-ref="mapContainer"
        >
            <div id="locations-map" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 z-0 relative" wire:ignore></div>
        </div>
    @endif

    {{-- List --}}
    @if($listItems->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($listItems as $item)
                @if($tab === 'breweries')
                    <div class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            @if($item->latitude && $item->longitude)
                                <button
                                    onclick="if(window._locMap){window._locMap.setView([{{ $item->latitude }},{{ $item->longitude }}],14);window.scrollTo({top:0,behavior:'smooth'})}"
                                    class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center hover:bg-amber-200 dark:hover:bg-amber-800/40 transition-colors cursor-pointer"
                                    title="Show on map"
                                >
                                    @if($item->logo_path)
                                        <img src="{{ Storage::url($item->logo_path) }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                    @endif
                                </button>
                            @else
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                    @if($item->logo_path)
                                        <img src="{{ Storage::url($item->logo_path) }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
                                    @endif
                                </div>
                            @endif
                            <a href="{{ route('beers.index', ['brewery' => $item->id]) }}" wire:navigate class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h3>
                                @php $loc = collect([$item->city, $item->state, $item->country])->filter()->implode(', '); @endphp
                                @if($loc)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $loc }}</p>
                                @endif
                            </a>
                            <span class="flex-shrink-0 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                {{ $item->beers_count }} {{ Str::plural('beer', $item->beers_count) }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            @if($item->latitude && $item->longitude)
                                <button
                                    onclick="if(window._locMap){window._locMap.setView([{{ $item->latitude }},{{ $item->longitude }}],14);window.scrollTo({top:0,behavior:'smooth'})}"
                                    class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center hover:bg-amber-200 dark:hover:bg-amber-800/40 transition-colors cursor-pointer"
                                    title="Show on map"
                                >
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                </button>
                            @else
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                </div>
                            @endif
                            <a href="{{ route('venues.show', $item) }}" wire:navigate class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h3>
                                @if($item->displayLocation())
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->displayLocation() }}</p>
                                @endif
                                @if($item->address)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ $item->address }}</p>
                                @endif
                            </a>
                            <span class="flex-shrink-0 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                {{ $item->checkins_count }} {{ Str::plural('check-in', $item->checkins_count) }}
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-6">
            {{ $listItems->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No {{ $tab === 'breweries' ? 'breweries' : 'venues' }} found.</p>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('locationMap', (points, mode) => ({
        map: null,
        init() {
            LeafletMap.loadLeaflet(() => this.renderMap(points, mode));
        },
        renderMap(points, mode) {
            this.map = LeafletMap.createMap('locations-map');
            if (!this.map) return;
            window._locMap = this.map;

            points.forEach(p => {
                const color = mode === 'breweries' ? '#f59e0b' : '#22c55e';
                const count = mode === 'breweries' ? (p.beers || 0) : (p.checkins || 0);
                const scale = mode === 'breweries' ? 3 : 2;
                const size = Math.min(24, Math.max(10, 8 + count * scale));
                const label = size > 14 ? count : '';

                let popup;
                if (mode === 'breweries') {
                    const beerLinks = (p.beerList || []).map(b =>
                        `<a href="/beers/${b.id}" style="color:#d97706;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${b.name}</a>`
                    ).join('<br>');
                    const moreText = p.hasMore ? `<br><span style="color:#999;font-size:11px">+ ${p.beers - 5} more</span>` : '';
                    popup = `<div style="min-width:180px;max-width:250px"><strong style="font-size:14px">${p.name}</strong>${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}<div style="margin-top:6px;font-size:12px;color:#888">${p.beers} ${p.beers === 1 ? 'beer' : 'beers'} &middot; ${p.checkins} ${p.checkins === 1 ? 'check-in' : 'check-ins'}</div>${beerLinks ? '<div style="margin-top:6px;font-size:12px;line-height:1.6">' + beerLinks + moreText + '</div>' : ''}</div>`;
                } else {
                    popup = `<div style="min-width:140px"><a href="/venues/${p.id}" style="font-size:14px;font-weight:700;color:#d97706;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${p.name}</a>${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}<div style="margin-top:6px;font-size:12px;color:#888">${p.checkins} ${p.checkins === 1 ? 'check-in' : 'check-ins'}</div></div>`;
                }

                LeafletMap.createMarker(this.map, p, { color, size, label, popup });
            });

            LeafletMap.fitBounds(this.map, points);
        }
    }));
</script>
@endscript
