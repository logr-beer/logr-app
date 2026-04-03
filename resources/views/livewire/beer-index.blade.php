<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Beers</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <a href="{{ route('beers.index') }}" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white">Library</a>
                <a href="{{ route('beers.inventory') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">Inventory</a>
            </div>

            {{-- Search & Filters (right-aligned) --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:ml-auto w-full sm:w-auto">
                <div class="relative w-full sm:w-56">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search beers or breweries..." class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500" />
                </div>
                <div class="flex items-center gap-2">
                    <select wire:model.live="style" class="flex-1 sm:flex-none px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500">
                        <option value="">All Styles</option>
                        @foreach($styles as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    <x-sort-control :options="['newest' => 'Newest', 'name' => 'Name', 'rating' => 'Rating', 'abv' => 'ABV']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Beer Grid --}}
    @if($beers->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($beers as $beer)
                <x-beer-card :beer="$beer" :selectable="true" :selected="in_array($beer->id, $selected)" />
            @endforeach
        </div>

        <div class="mt-8 {{ count($selected) > 0 ? 'mb-24' : '' }}">
            {{ $beers->links('vendor.livewire.custom-pagination') }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No beers found</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                @if($search || $style)
                    Try adjusting your search or filters.
                @else
                    Start by adding some beers to your library.
                @endif
            </p>
            @if(!$search && !$style)
                <a href="{{ route('beers.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors">
                    Add Your First Beer
                </a>
            @endif
        </div>
    @endif

    {{-- Floating Action Bar (visible when any cards are selected) --}}
    @if(count($selected) > 0)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 dark:bg-gray-700 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center gap-3 max-w-[95vw]">
            <span class="text-sm font-medium whitespace-nowrap">{{ count($selected) }} selected</span>

            <div class="w-px h-6 bg-gray-600"></div>

            <button wire:click="selectAll" class="text-sm text-amber-400 hover:text-amber-300 whitespace-nowrap">All</button>
            <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-gray-200 whitespace-nowrap">None</button>

            <div class="w-px h-6 bg-gray-600"></div>

            {{-- Add to Collection --}}
            <button
                wire:click="openCollectionModal"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                Collection
            </button>

            {{-- Add to Inventory --}}
            <button
                wire:click="openInventoryModal"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                Inventory
            </button>

            {{-- Delete --}}
            <button
                wire:click="deleteSelected"
                wire:confirm="Delete {{ count($selected) }} beer(s) and all their check-ins? This cannot be undone."
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                Delete
            </button>

            <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-white transition-colors ml-1">Cancel</button>
        </div>
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
