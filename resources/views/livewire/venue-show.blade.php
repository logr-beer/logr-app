<div>
    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('locations.venues') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to Venues
        </a>
    </div>

    {{-- Venue Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        @if($editing)
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
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
                    {{-- Coordinates --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Coordinates</label>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex-1">
                                <input type="text" wire:model="latitude" id="venue-lat" placeholder="Latitude" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                @error('latitude') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex-1">
                                <input type="text" wire:model="longitude" id="venue-lng" placeholder="Longitude" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                @error('longitude') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="lookupCoordinates" class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 border border-amber-300 dark:border-amber-700 rounded-lg transition-colors">
                                <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="lookupCoordinates" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                Lookup
                            </button>
                        </div>
                        @if($geocodeError)
                            <p class="text-sm {{ str_starts_with($geocodeError, 'Exact') ? 'text-amber-500' : 'text-red-500' }} mb-2">{{ $geocodeError }}</p>
                        @endif

                        {{-- Mini map --}}
                        <div
                            x-data="venuePickerMap({
                                lat: $wire.latitude,
                                lng: $wire.longitude,
                            })"
                            x-init="init()"
                            x-on:coords-updated.window="updateFromLivewire($event.detail)"
                            wire:ignore
                        >
                            <div id="venue-picker-map" class="w-full h-[250px] rounded-lg border border-gray-300 dark:border-gray-600"></div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Drag the pin or click the map to set coordinates</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">Save</button>
                    <button type="button" wire:click="cancel" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                    @unless(config('app.demo_mode'))
                        <button
                            type="button"
                            wire:click="delete"
                            wire:confirm="Delete this venue? This cannot be undone."
                            class="ml-auto px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"
                        >Delete</button>
                    @endunless
                </div>
            </form>
        @else
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $venue->name }}</h1>
                    @if($venue->displayLocation())
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $venue->displayLocation() }}</p>
                    @endif
                    @if($venue->address)
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $venue->address }}</p>
                    @endif
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {{ $checkins->count() }} {{ Str::plural('check-in', $checkins->count()) }}
                    </p>
                </div>
                <button wire:click="edit" class="flex-shrink-0 p-2 text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors" title="Edit venue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                </button>
            </div>
        @endif
    </div>

    {{-- Check-ins --}}
    @if($checkins->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No check-ins at this venue yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($checkins as $checkin)
                <a href="{{ route('beers.show', $checkin->beer) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-4">
                        {{-- Beer photo --}}
                        <div class="flex-shrink-0">
                            <div class="w-16 h-20 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                @if($checkin->beer->photo_path)
                                    <img src="{{ Storage::url($checkin->beer->photo_path) }}" alt="{{ $checkin->beer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                        <x-application-logo class="w-8 h-8 stroke-current" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                        {{ $checkin->beer->name }}
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $checkin->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                                </div>
                                <time class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap" datetime="{{ $checkin->created_at->toISOString() }}">
                                    {{ $checkin->created_at->diffForHumans() }}
                                </time>
                            </div>

                            {{-- Rating Stars --}}
                            @if($checkin->rating !== null)
                                <div class="flex items-center gap-0.5 mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $checkin->rating)
                                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                                        @endif
                                    @endfor
                                    <span class="ml-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ number_format($checkin->rating, 1) }}</span>
                                </div>
                            @endif

                            {{-- Serving Type --}}
                            @if($checkin->serving_type)
                                <span class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ ucfirst($checkin->serving_type) }}
                                </span>
                            @endif

                            {{-- Notes --}}
                            @if($checkin->notes)
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ $checkin->notes }}</p>
                            @endif

                            {{-- Photos --}}
                            @if($checkin->photos->isNotEmpty())
                                <div class="flex gap-2 mt-3 overflow-x-auto">
                                    @foreach($checkin->photos as $photo)
                                        <img src="{{ Storage::url($photo->photo_path) }}" alt="Check-in photo" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

@if($editing)
@script
<script>
    Alpine.data('venuePickerMap', (config) => ({
        map: null,
        marker: null,

        init() {
            // Load Leaflet CSS
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
            const el = document.getElementById('venue-picker-map');
            if (!el || !window.L) return;

            const lat = parseFloat(config.lat) || 44.98;
            const lng = parseFloat(config.lng) || -85.72;
            const hasCoords = config.lat && config.lng;

            this.map = L.map(el).setView([lat, lng], hasCoords ? 15 : 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

            // Update Livewire on drag end
            this.marker.on('dragend', () => {
                const pos = this.marker.getLatLng();
                $wire.set('latitude', pos.lat.toFixed(7));
                $wire.set('longitude', pos.lng.toFixed(7));
            });

            // Click map to move pin
            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                $wire.set('latitude', e.latlng.lat.toFixed(7));
                $wire.set('longitude', e.latlng.lng.toFixed(7));
            });

            // Watch for Livewire coordinate changes (e.g. from lookup)
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
