<div>
    {{-- Header --}}
    <div class="mb-6">
        <x-page-header title="Locations" actionLabel="New" actionClick="createNew" />
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <x-pill-tabs
                :tabs="[
                    'breweries' => $type === 'brewery' ? 'Breweries' : ['label' => 'Breweries', 'href' => route('locations.breweries')],
                    'venues' => $type === 'venue' ? 'Venues' : ['label' => 'Venues', 'href' => route('locations.venues')],
                    'stores' => $type === 'store' ? 'Stores' : ['label' => 'Stores', 'href' => route('locations.stores')],
                ]"
                :active="match($type) { 'brewery' => 'breweries', 'venue' => 'venues', 'store' => 'stores' }"
            />

            {{-- Search & Sort --}}
            <div class="flex items-center gap-2 sm:ml-auto">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search {{ strtolower($config['label']) }}..." class="flex-1 min-w-0" />
                <x-sort-control :options="$config['sortOptions']" />

                {{-- Geocode button --}}
                @if($geocodingEnabled && $config['canGeocode'] && $ungeocodedCount > 0)
                    <button
                        wire:click="geocodeBatch"
                        class="relative p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:text-amber-500 hover:border-amber-500 transition-colors flex-shrink-0"
                        title="Look up coordinates for {{ $ungeocodedCount }} {{ Str::plural($config['singular'], $ungeocodedCount) }}"
                    >
                        <x-icon name="refresh" size="4" class="{{ $geocoding ? 'animate-spin' : '' }}" />
                        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-amber-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $ungeocodedCount }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Map --}}
    @if($mapPoints->isNotEmpty())
        <div
            x-data="locationIndexMap({{ $mapPoints->values()->toJson() }}, '{{ $config['countLabel'] }}')"
            x-init="init()"
            class="mb-6"
        >
            <div id="{{ $config['mapId'] }}" class="w-full h-[400px] rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 z-0 relative" wire:ignore aria-hidden="true" tabindex="-1"></div>
        </div>
    @endif

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
                <a href="{{ route($config['showRoute'], $item) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center overflow-hidden">
                            @if($item->logo_path)
                                <img src="{{ Storage::url($item->logo_path) }}" alt="{{ $item->name }}" class="w-10 h-10 object-cover">
                            @else
                                <x-icon :name="$config['icon']" size="5" class="text-amber-600 dark:text-amber-400" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h3>
                            @if($item->displayLocation())
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->displayLocation() }}</p>
                            @endif
                            @if($item->address)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $item->address }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if((!$item->latitude || !$item->longitude) && !($type === 'venue' && $item->is_home))
                                <span title="Missing location data" class="text-amber-500">
                                    <x-icon name="warning" size="4" />
                                </span>
                            @endif
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium">
                                {{ $item->{$config['countRelation'].'_count'} }} {{ Str::plural($config['countLabel'], $item->{$config['countRelation'].'_count'}) }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $listItems->links() }}
        </div>
    @else
        <x-empty-state
            :title="$config['emptyTitle']"
            :message="$config['emptyMessage']"
        />
    @endif
</div>

@script
<script>
    Alpine.data('locationIndexMap', (points, countLabel) => ({
        map: null,
        init() {
            LeafletMap.loadLeaflet(() => this.renderMap(points));
        },
        renderMap(points) {
            this.map = LeafletMap.createMap('{{ $config['mapId'] }}');
            if (!this.map) return;

            points.forEach(p => {
                const popup = `<strong>${p.name}</strong>${p.location ? '<br><span style="color:#666;font-size:12px">' + p.location + '</span>' : ''}<br><span style="color:#999;font-size:11px">${p.count} ${p.count === 1 ? countLabel : countLabel + 's'}</span>`;
                LeafletMap.createMarker(this.map, p, { color: '#f59e0b', size: 12, popup });
            });

            LeafletMap.fitBounds(this.map, points);
        }
    }));
</script>
@endscript
