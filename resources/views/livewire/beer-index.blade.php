<div>
    {{-- Header --}}
    <div class="mb-6">
        <x-page-header title="Beers" actionLabel="Add" :actionHref="route('beers.create')" />
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <x-pill-tabs
                :tabs="['all' => 'All Beers', 'favorites' => 'Favorites', 'inventory' => ['label' => 'Inventory', 'href' => route('beers.inventory')]]"
                :active="$filter"
                wireModel="filter"
            />

            {{-- Search & Filters (right-aligned) --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:ml-auto w-full sm:w-auto">
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search beers or breweries..." />
                <div class="flex items-center gap-2">
                    <div class="flex-1 sm:flex-none sm:w-40">
                        <x-custom-select :options="array_merge(['' => 'All Styles'], array_combine($styles, $styles))" wireModel="style" placeholder="All Styles" />
                    </div>
                    <x-sort-control :options="['newest' => 'Newest', 'name' => 'Name', 'rating' => 'Rating', 'abv' => 'ABV']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Beer Grid --}}
    @if($beers->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($beers as $beer)
                <x-beer-card :beer="$beer" :selectable="true" :selected="in_array($beer->id, $selected)" />
            @endforeach
        </div>

        <div class="mt-8 {{ count($selected) > 0 ? 'mb-24' : '' }}">
            {{ $beers->links('vendor.livewire.custom-pagination') }}
        </div>
    @else
        <x-empty-state
            title="No beers found"
            :message="$search || $style ? 'Try adjusting your search or filters.' : 'Start by adding some beers to your library.'"
            :actionLabel="!$search && !$style ? 'Add Your First Beer' : null"
            :actionHref="!$search && !$style ? route('beers.create') : null"
        />
    @endif

    {{-- Floating Action Bar (visible when any cards are selected) --}}
    @if(count($selected) > 0)
        <x-floating-action-bar :count="count($selected)">
            <button wire:click="openCollectionModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                <x-icon name="folder-plus" size="4" /> Collection
            </button>
            <button wire:click="openInventoryModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                <x-icon name="archive-box" size="4" /> Inventory
            </button>
            @unless(config('app.demo_mode'))
                <button wire:click="deleteSelected" wire:confirm="Delete {{ count($selected) }} beer(s) and all their check-ins? This cannot be undone." class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                    <x-icon name="trash" size="4" /> Delete
                </button>
            @endunless
        </x-floating-action-bar>
    @endif

    {{-- Collection Modal --}}
    @if($showCollectionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showCollectionModal', false)">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showCollectionModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Add to Collection</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add {{ count($selected) }} beer(s) to a collection.</p>

                @if($collections->isNotEmpty())
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        @foreach($collections as $collection)
                            <button
                                wire:click="addSelectedToCollection({{ $collection->id }})"
                                class="w-full text-left px-4 py-3 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-between"
                            >
                                <span class="font-medium">{{ $collection->name }}</span>
                                <span class="text-xs text-gray-400">{{ $collection->resolveBeersCount() }} beers</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">No collections yet. Create one first.</p>
                @endif

                <div class="mt-4 flex justify-end">
                    <button wire:click="$set('showCollectionModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Inventory Modal --}}
    @if($showInventoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showInventoryModal', false)">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showInventoryModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Add to Inventory</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add {{ count($selected) }} beer(s) to your inventory.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage Location</label>
                        <input type="text" wire:model="inventoryLocation" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder="e.g. Fridge, Cellar, Garage" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity (each)</label>
                        <input type="number" wire:model="inventoryQuantity" min="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showInventoryModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                    <button wire:click="addSelectedToInventory" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Add to Inventory</button>
                </div>
            </div>
        </div>
    @endif
</div>
