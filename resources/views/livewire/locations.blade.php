<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Locations</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $stats['mapped'] }} on the map
                @if($stats['unmapped'] > 0)
                    &middot; {{ $stats['unmapped'] }} without coordinates
                @endif
            </p>
        </div>

        {{-- Tabs --}}
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
            <button
                wire:click="$set('tab', 'breweries')"
                class="px-4 py-2 text-sm font-medium {{ $tab === 'breweries' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            >
                Breweries
            </button>
            <button
                wire:click="$set('tab', 'checkins')"
                class="px-4 py-2 text-sm font-medium {{ $tab === 'checkins' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            >
                Check-ins
            </button>
        </div>
    </div>

    {{-- Breweries Map --}}
    @if($tab === 'breweries')
        @if($mapBreweries->count() > 0)
            <div
                x-data="locationMap({{ $mapBreweries->values()->toJson() }}, 'breweries')"
                x-init="init()"
                class="mb-6"
            >
                <div id="locations-map" class="w-full h-[600px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore></div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">No breweries with coordinates yet.</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Import beers with brewery locations, then run <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">php artisan breweries:geocode</code> to populate the map.</p>
            </div>
        @endif
    @endif

    {{-- Check-ins Map --}}
    @if($tab === 'checkins')
        @if($mapVenues->count() > 0)
            <div
                x-data="locationMap({{ $mapVenues->values()->toJson() }}, 'checkins')"
                x-init="init()"
                class="mb-6"
            >
                <div id="locations-map" class="w-full h-[600px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" wire:ignore></div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">No venues with coordinates yet.</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Venues will appear here when you check in at locations with coordinates.</p>
            </div>
        @endif
    @endif
</div>

@script
<script>
    Alpine.data('locationMap', (points, mode) => ({
        map: null,
        init() {
            if (!document.querySelector('link[href*="leaflet"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.crossOrigin = '';
                document.head.appendChild(link);
            }

            if (!window.L) {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.crossOrigin = '';
                script.onload = () => this.renderMap(points, mode);
                document.head.appendChild(script);
            } else {
                this.renderMap(points, mode);
            }
        },
        renderMap(points, mode) {
            const el = document.getElementById('locations-map');
            if (!el || !window.L) return;

            if (el._leaflet_id) {
                el._leaflet_id = null;
                el.innerHTML = '';
            }

            this.map = L.map(el).setView([39.8, -98.5], 4);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            const markers = [];

            points.forEach(p => {
                let size, popup;

                if (mode === 'breweries') {
                    size = Math.min(24, Math.max(10, 8 + (p.beers || 0) * 3));
                    const beerLinks = (p.beerList || []).map(b =>
                        `<a href="/beers/${b.id}" style="color:#d97706;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${b.name}</a>`
                    ).join('<br>');
                    const moreText = p.hasMore ? `<br><span style="color:#999;font-size:11px">+ ${p.beers - 5} more</span>` : '';
                    popup = `
                        <div style="min-width:180px;max-width:250px">
                            <strong style="font-size:14px">${p.name}</strong>
                            ${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}
                            <div style="margin-top:6px;font-size:12px;color:#888">
                                ${p.beers} ${p.beers === 1 ? 'beer' : 'beers'} &middot; ${p.checkins} ${p.checkins === 1 ? 'check-in' : 'check-ins'}
                            </div>
                            ${beerLinks ? '<div style="margin-top:6px;font-size:12px;line-height:1.6">' + beerLinks + moreText + '</div>' : ''}
                        </div>`;
                } else {
                    size = Math.min(24, Math.max(10, 8 + (p.checkins || 0) * 2));
                    popup = `
                        <div style="min-width:140px">
                            <a href="/venues/${p.id}" style="font-size:14px;font-weight:700;color:#d97706;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${p.name}</a>
                            ${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}
                            <div style="margin-top:6px;font-size:12px;color:#888">
                                ${p.checkins} ${p.checkins === 1 ? 'check-in' : 'check-ins'}
                            </div>
                        </div>`;
                }

                const color = mode === 'breweries' ? '#f59e0b' : '#22c55e';
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="background:${color};width:${size}px;height:${size}px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-size:${size > 14 ? '9' : '0'}px;font-weight:700">${size > 14 ? (mode === 'breweries' ? p.beers : p.checkins) : ''}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size/2, size/2],
                    popupAnchor: [0, -(size/2 + 2)],
                });

                const marker = L.marker([p.lat, p.lng], { icon })
                    .addTo(this.map)
                    .bindPopup(popup);
                markers.push(marker);
            });

            if (markers.length > 0) {
                // Fit to main cluster, excluding distant outliers
                const lats = points.map(p => p.lat).sort((a, b) => a - b);
                const lngs = points.map(p => p.lng).sort((a, b) => a - b);
                const q1Lat = lats[Math.floor(lats.length * 0.05)];
                const q3Lat = lats[Math.floor(lats.length * 0.95)];
                const q1Lng = lngs[Math.floor(lngs.length * 0.05)];
                const q3Lng = lngs[Math.floor(lngs.length * 0.95)];
                const iqrLat = (q3Lat - q1Lat) || 1;
                const iqrLng = (q3Lng - q1Lng) || 1;

                const inliers = points.filter(p =>
                    p.lat >= q1Lat - iqrLat * 1.5 && p.lat <= q3Lat + iqrLat * 1.5 &&
                    p.lng >= q1Lng - iqrLng * 1.5 && p.lng <= q3Lng + iqrLng * 1.5
                );

                const fitPoints = inliers.length > 0 ? inliers : points;
                const bounds = L.latLngBounds(fitPoints.map(p => [p.lat, p.lng]));
                this.map.fitBounds(bounds.pad(0.1));
            }
        }
    }));
</script>
@endscript
