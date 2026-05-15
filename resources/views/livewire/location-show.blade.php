<div>
    <x-back-link :href="$config['backRoute']" :label="$config['backLabel']" />

    {{-- Location Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        @if($editing)
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2" x-data="{ nameOpen: false }" @click.outside="nameOpen = false">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <div class="relative">
                            <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                wire:model.live.debounce.400ms="name"
                                @focus="nameOpen = true"
                                @input="nameOpen = true"
                                autocomplete="off"
                                placeholder="Search for a place..."
                                class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            />
                            @if(count($nameSearchResults) > 0)
                                <div x-show="nameOpen" x-cloak x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($nameSearchResults as $i => $result)
                                        <button
                                            type="button"
                                            wire:click="selectNameResult({{ $i }})"
                                            @click="nameOpen = false"
                                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                                        >
                                            <x-icon name="map-pin" size="4" class="text-amber-500 flex-shrink-0" />
                                            <div class="min-w-0">
                                                <span class="text-gray-900 dark:text-white">{{ $result['name'] }}</span>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ collect([$result['address'], $result['city'], $result['state'], $result['country']])->filter()->implode(', ') }}</p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <input type="text" wire:model="address" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                        <input type="text" wire:model="city" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State</label>
                        <input type="text" wire:model="state" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                        <input type="text" wire:model="country" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="url" wire:model="website" placeholder="https://..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                        @error('website') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    {{-- Coordinates --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Coordinates</label>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex-1">
                                <input type="text" wire:model="latitude" placeholder="Latitude" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                @error('latitude') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex-1">
                                <input type="text" wire:model="longitude" placeholder="Longitude" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                @error('longitude') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="lookupCoordinates" class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 border border-amber-300 dark:border-amber-700 rounded-lg transition-colors">
                                <x-icon name="map-pin" size="4" wire:loading.class="animate-spin" wire:target="lookupCoordinates" />
                                Lookup
                            </button>
                        </div>
                        @if($geocodeError)
                            <p class="text-sm {{ str_starts_with($geocodeError, 'Exact') ? 'text-amber-500' : 'text-red-500' }} mb-2">{{ $geocodeError }}</p>
                        @endif

                        <div
                            x-data="locationPickerMap({ lat: $wire.latitude, lng: $wire.longitude })"
                            x-init="init()"
                            wire:ignore
                        >
                            <div id="location-picker-map" class="w-full h-[250px] rounded-lg border border-gray-300 dark:border-gray-600"></div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Drag the pin or click the map to set coordinates</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-3">
                        @unless(config('app.demo_mode'))
                            <button
                                type="button"
                                wire:click="delete"
                                wire:confirm="Delete this {{ $type }}? This cannot be undone."
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                            >
                                <x-icon name="trash" size="4" />
                                Delete
                            </button>
                        @endunless
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="cancel" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                        <x-primary-button size="lg">Save</x-primary-button>
                    </div>
                </div>
            </form>
        @else
            <div class="flex flex-col md:flex-row gap-6">
                {{-- Details --}}
                <div class="flex items-start gap-4 flex-1">
                    <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center overflow-hidden">
                        @if($location->logo_path)
                            <img src="{{ Storage::url($location->logo_path) }}" alt="{{ $location->name }}" class="w-12 h-12 object-cover">
                        @else
                            <x-icon :name="$config['icon']" size="6" class="text-amber-600 dark:text-amber-400" />
                        @endif
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $location->name }}</h1>
                        @if($location->displayLocation())
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $location->displayLocation() }}</p>
                        @endif
                        @if($location->address)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $location->address }}</p>
                        @endif
                        @if($location->website)
                            <a href="{{ $location->website }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400 hover:underline mt-1">
                                <x-icon name="external-link" size="3.5" />
                                {{ parse_url($location->website, PHP_URL_HOST) }}
                            </a>
                        @endif
                        <div class="flex items-center gap-2 mt-2">
                            @if($totalCheckins > 0)
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                    {{ $totalCheckins }} {{ Str::plural('check-in', $totalCheckins) }}
                                </span>
                            @endif
                            @if($totalInventory > 0)
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                    {{ $totalInventory }} in inventory
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="refreshLocation" class="p-2 text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors" title="Re-lookup location data">
                            <x-icon name="refresh" size="5" wire:loading.class="animate-spin" wire:target="refreshLocation" />
                        </button>
                        <button wire:click="edit" class="p-2 text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors" title="Edit {{ $type }}">
                            <x-icon name="pencil" size="5" />
                        </button>
                    </div>
                </div>

                {{-- Mini map --}}
                @if($location->latitude && $location->longitude)
                    <div
                        x-data="locationMiniMap({{ $location->latitude }}, {{ $location->longitude }}, '{{ addslashes($location->name) }}')"
                        x-init="init()"
                        class="w-full md:w-72 flex-shrink-0"
                        wire:ignore
                    >
                        <div id="location-mini-map" class="w-full h-[180px] rounded-lg border border-gray-200 dark:border-gray-700"></div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Content Sections --}}
    @if($type === 'brewery')
        {{-- Two-column: Check-ins + Inventory --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Recent Check-ins</h2>
                @if($checkins->isEmpty())
                    <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <x-icon name="check-circle" size="12" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400">No check-ins yet.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($checkins as $checkin)
                            @include('livewire.partials.location-checkin-card', ['checkin' => $checkin])
                        @endforeach
                    </div>
                    @if($totalCheckins > $checkinLimit)
                        <button wire:click="loadMoreCheckins" class="mt-3 w-full py-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                            Load more ({{ $totalCheckins - $checkinLimit }} remaining)
                        </button>
                    @endif
                @endif
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Inventory</h2>
                @if($inventoryItems->isEmpty())
                    <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <x-icon name="archive-box" size="12" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400">No inventory yet.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($inventoryItems as $item)
                            @include('livewire.partials.location-inventory-card', ['item' => $item])
                        @endforeach
                    </div>
                    @if($totalInventory > $inventoryLimit)
                        <button wire:click="loadMoreInventory" class="mt-3 w-full py-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                            Load more ({{ $totalInventory - $inventoryLimit }} remaining)
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @elseif($type === 'venue')
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Recent Check-ins</h2>
        @if($checkins->isEmpty())
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <x-icon name="check-circle" size="16" class="text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">No check-ins at this venue yet.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($checkins as $checkin)
                    @include('livewire.partials.location-checkin-card', ['checkin' => $checkin])
                @endforeach
            </div>
            @if($totalCheckins > $checkinLimit)
                <button wire:click="loadMoreCheckins" class="mt-4 w-full py-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                    Load more ({{ $totalCheckins - $checkinLimit }} remaining)
                </button>
            @endif
        @endif
    @elseif($type === 'store')
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Purchases</h2>
        @if($inventoryItems->isEmpty())
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <x-icon name="archive-box" size="16" class="text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">No purchases from this store yet.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($inventoryItems as $item)
                    @include('livewire.partials.location-inventory-card', ['item' => $item])
                @endforeach
            </div>
            @if($totalInventory > $inventoryLimit)
                <button wire:click="loadMoreInventory" class="mt-4 w-full py-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                    Load more ({{ $totalInventory - $inventoryLimit }} remaining)
                </button>
            @endif
        @endif
    @endif
</div>

@if($editing)
@script
<script>
    Alpine.data('locationPickerMap', (config) => ({
        map: null,
        marker: null,

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
                script.onload = () => this.renderMap();
                document.head.appendChild(script);
            } else {
                this.renderMap();
            }
        },

        renderMap() {
            const el = document.getElementById('location-picker-map');
            if (!el || !window.L) return;

            const lat = parseFloat(config.lat) || 44.98;
            const lng = parseFloat(config.lng) || -85.72;
            const hasCoords = config.lat && config.lng;

            this.map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], hasCoords ? 15 : 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

            this.marker.on('dragend', () => {
                const pos = this.marker.getLatLng();
                $wire.set('latitude', pos.lat.toFixed(7));
                $wire.set('longitude', pos.lng.toFixed(7));
            });

            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                $wire.set('latitude', e.latlng.lat.toFixed(7));
                $wire.set('longitude', e.latlng.lng.toFixed(7));
            });

            $wire.$watch('latitude', (val) => {
                const lat = parseFloat(val);
                const lng = parseFloat($wire.longitude);
                if (!isNaN(lat) && !isNaN(lng) && this.marker) {
                    this.marker.setLatLng([lat, lng]);
                    this.map.setView([lat, lng], Math.max(this.map.getZoom(), 13));
                }
            });
            $wire.$watch('longitude', (val) => {
                const lat = parseFloat($wire.latitude);
                const lng = parseFloat(val);
                if (!isNaN(lat) && !isNaN(lng) && this.marker) {
                    this.marker.setLatLng([lat, lng]);
                    this.map.setView([lat, lng], Math.max(this.map.getZoom(), 13));
                }
            });
        },
    }));
</script>
@endscript
@endif

@if(!$editing && $location->latitude && $location->longitude)
@script
<script>
    Alpine.data('locationMiniMap', (lat, lng, name) => ({
        init() {
            LeafletMap.loadLeaflet(() => {
                const map = LeafletMap.createMap('location-mini-map', { center: [lat, lng], zoom: 14 });
                if (!map) return;
                LeafletMap.createMarker(map, { lat, lng }, { color: '#f59e0b', size: 12, popup: `<strong>${name}</strong>` });
            });
        }
    }));
</script>
@endscript
@endif
