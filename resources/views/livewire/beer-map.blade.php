<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Beer Map</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $mappedBreweries }} {{ Str::plural('brewery', $mappedBreweries) }} on the map
                @if($unmappedBreweries > 0)
                    &middot; {{ $unmappedBreweries }} without coordinates
                @endif
            </p>
        </div>

        @if($unmappedBreweries > 0)
            <div class="text-xs text-gray-400 dark:text-gray-500">
                Run <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">php artisan breweries:geocode</code> to map remaining breweries
            </div>
        @endif
    </div>

    {{-- Map --}}
    @if($mapBreweries->count() > 0)
        <div
            x-data="breweryMap({{ $mapBreweries->values()->toJson() }})"
            x-init="init()"
            class="mb-6"
        >
            <div id="brewery-map" class="w-full h-[600px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore></div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m0-8.25a1.5 1.5 0 0 1 3 0V15m3-12.75V15"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No breweries with coordinates yet.</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Import beers with brewery locations, then run <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">php artisan breweries:geocode</code> to populate the map.</p>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('breweryMap', (breweries) => ({
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

            // Load Leaflet JS
            if (!window.L) {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.crossOrigin = '';
                script.onload = () => this.renderMap(breweries);
                document.head.appendChild(script);
            } else {
                this.renderMap(breweries);
            }
        },
        renderMap(breweries) {
            const el = document.getElementById('brewery-map');
            if (!el || !window.L) return;

            if (el._leaflet_id) return;

            this.map = L.map(el).setView([39.8, -98.5], 4);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            const markers = [];

            breweries.forEach(b => {
                const size = Math.min(24, Math.max(10, 8 + b.beers * 3));

                const icon = L.divIcon({
                    className: '',
                    html: `<div style="background:#f59e0b;width:${size}px;height:${size}px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-size:${size > 14 ? '9' : '0'}px;font-weight:700">${size > 14 ? b.beers : ''}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size/2, size/2],
                    popupAnchor: [0, -(size/2 + 2)],
                });

                const popup = `
                    <div style="min-width:160px">
                        <strong style="font-size:14px">${b.name}</strong>
                        ${b.location ? '<br><span style="color:#666;font-size:12px">' + b.location + '</span>' : ''}
                        <div style="margin-top:6px;display:flex;gap:12px;font-size:12px;color:#888">
                            <span>🍺 ${b.beers} ${b.beers === 1 ? 'beer' : 'beers'}</span>
                            <span>✓ ${b.checkins} ${b.checkins === 1 ? 'check-in' : 'check-ins'}</span>
                        </div>
                    </div>
                `;

                const marker = L.marker([b.lat, b.lng], { icon })
                    .addTo(this.map)
                    .bindPopup(popup);
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
