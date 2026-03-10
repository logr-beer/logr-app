<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Venues</h1>

        <div class="flex items-center gap-3">
            {{-- View toggle --}}
            <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                <button
                    wire:click="$set('view', 'list')"
                    class="px-3 py-2 text-sm {{ $view === 'list' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                </button>
                <button
                    wire:click="$set('view', 'map')"
                    class="px-3 py-2 text-sm {{ $view === 'map' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m0-8.25a1.5 1.5 0 0 1 3 0V15m3-12.75V15m0-8.25a1.5 1.5 0 0 1 3 0V15M3.75 4.5h16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                </button>
            </div>

            {{-- Search --}}
            <div class="relative flex-1 sm:w-64">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search venues..."
                    class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
                />
            </div>

            {{-- Sort --}}
            <select
                wire:model.live="sortBy"
                class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
            >
                <option value="checkins">Most Check-ins</option>
                <option value="name">Name</option>
                <option value="recent">Recently Visited</option>
            </select>
        </div>
    </div>

    {{-- Map View --}}
    @if($view === 'map')
        @if($mapVenues->count() > 0)
            <div
                x-data="venueMap({{ $mapVenues->toJson() }})"
                x-init="init()"
                class="mb-6"
            >
                <div id="venue-map" class="w-full h-[500px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore></div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center mb-6">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                <p class="text-gray-500 dark:text-gray-400">No venues with coordinates yet.</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Scrape venues from your Untappd profile to populate the map.</p>
            </div>
        @endif
    @endif

    {{-- List View --}}
    @if($view === 'list')
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
    @endif
</div>

@if($view === 'map')
    @script
    <script>
        Alpine.data('venueMap', (venues) => ({
            map: null,
            init() {
                // Load Leaflet CSS
                if (!document.querySelector('link[href*="leaflet"]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    link.crossOrigin = '';
                    document.head.appendChild(link);
                }

                // Load Leaflet JS dynamically
                if (!window.L) {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.crossOrigin = '';
                    script.onload = () => this.renderMap(venues);
                    document.head.appendChild(script);
                } else {
                    this.renderMap(venues);
                }
            },
            renderMap(venues) {
                const el = document.getElementById('venue-map');
                if (!el || !window.L) return;

                this.map = L.map(el).setView([42.96, -85.67], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);

                const markers = [];
                const amberIcon = L.divIcon({
                    className: '',
                    html: '<div style="background:#f59e0b;width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3)"></div>',
                    iconSize: [12, 12],
                    iconAnchor: [6, 6],
                    popupAnchor: [0, -8],
                });

                venues.forEach(v => {
                    const marker = L.marker([v.lat, v.lng], { icon: amberIcon })
                        .addTo(this.map)
                        .bindPopup(`<strong>${v.name}</strong>${v.location ? '<br><span style="color:#666;font-size:12px">' + v.location + '</span>' : ''}<br><span style="color:#999;font-size:11px">${v.checkins} check-in${v.checkins !== 1 ? 's' : ''}</span>`);
                    markers.push(marker);
                });

                if (markers.length > 0) {
                    const group = L.featureGroup(markers);
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            }
        }));
    </script>
    @endscript
@endif
