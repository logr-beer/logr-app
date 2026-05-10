<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collections</h1>
            <button
                wire:click="$set('showCreateModal', true)"
                class="inline-flex items-center gap-1 px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium rounded-md transition-colors flex-shrink-0"
            >
                <x-icon name="plus" size="3.5" />
                New
            </button>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <x-pill-tabs
                :tabs="['all' => 'All', 'curated' => 'Curated', 'dynamic' => 'Dynamic']"
                :active="$collectionFilter"
                wireModel="collectionFilter"
            />

            {{-- Search (full width on mobile) --}}
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search collections..." class="w-full sm:w-56 sm:ml-auto" />

            <x-sort-control :options="['newest' => 'Newest', 'name' => 'Name', 'count' => 'Beers']" />
        </div>
    </div>

    {{-- Collections Grid --}}
    @if($collections->isNotEmpty() && in_array($collectionFilter, ['all', 'curated']))
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Curated Collections</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <div class="aspect-[4/3] bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                        @if($collection->cover_path)
                            <img src="{{ Storage::url($collection->cover_path) }}" alt="{{ $collection->name }}" class="w-full h-full object-cover">
                        @else
                            <x-icon name="collection" size="12" class="text-white/80" />
                        @endif
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $collection->name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $collection->beers_count }} {{ Str::plural('beer', $collection->beers_count) }}</p>
                        @if($collection->description)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 line-clamp-2">{{ $collection->description }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Dynamic Collections --}}
    @if($dynamicCollections->isNotEmpty() && in_array($collectionFilter, ['all', 'dynamic']))
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-icon name="sparkle" size="5" class="text-purple-500" />
            Dynamic Collections
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($dynamicCollections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <div class="aspect-[4/3] bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                        <x-icon name="sparkle" size="12" class="text-white/80" />
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $collection->name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $collection->dynamic_count }} {{ Str::plural('beer', $collection->dynamic_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if($collections->isEmpty() && $dynamicCollections->isEmpty())
        <x-empty-state
            :card="true"
            :title="$search ? 'No collections found' : 'No collections yet'"
            :message="$search ? 'Try a different search term.' : 'Create your first collection to start organizing your beers.'"
        />
    @endif

    {{-- Create Collection Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showCreateModal', false)">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">New Collection</h3>

                {{-- Tabs --}}
                <div class="mb-5">
                    <x-pill-tabs
                        :tabs="['collection' => 'Curated', 'dynamic' => 'Dynamic']"
                        :active="$createTab"
                        wireModel="createTab"
                    />
                </div>

                {{-- Collection Form --}}
                @if($createTab === 'collection')
                    <form wire:submit="createCollection" class="space-y-4">
                        <x-form-field label="Name" name="name">
                            <input type="text" id="name" wire:model="name" placeholder="e.g. Summer Favorites, IPAs to Try..." class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                        </x-form-field>
                        <x-form-field label="Description" name="description" :optional="true">
                            <textarea id="description" wire:model="description" rows="2" placeholder="What's this collection about?" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </x-form-field>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">Create Collection</button>
                        </div>
                    </form>
                @endif

                {{-- Dynamic Collection Form --}}
                @if($createTab === 'dynamic')
                    <form wire:submit="createDynamicCollection" class="space-y-4">
                        <x-form-field label="Rule">
                            <select wire:model.live="dynamicType" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Select a rule...</option>
                                <option value="year">Check-in Year</option>
                                <option value="style">Style</option>
                                <option value="rating">Minimum Rating</option>
                                <option value="abv">ABV Range</option>
                                <option value="brewery">Brewery</option>
                                <option value="serving_type">Serving Type</option>
                                <option value="venue">Venue</option>
                                <option value="favorites">Favorites</option>
                                <option value="oldest_in_stock">Oldest in Stock</option>
                            </select>
                        </x-form-field>

                        @if($dynamicType === 'year')
                            <x-form-field label="Year">
                                <input type="number" wire:model="dynamicYear" min="2000" max="{{ date('Y') }}" placeholder="e.g. {{ date('Y') }}" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            </x-form-field>
                        @elseif($dynamicType === 'style')
                            <x-form-field label="Style">
                                <select wire:model="dynamicStyle" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Select a style...</option>
                                    @foreach(config('beer-styles.flat') as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                        @elseif($dynamicType === 'rating')
                            <x-form-field label="Minimum Rating">
                                <input type="number" wire:model="dynamicMinRating" step="0.25" min="0.25" max="5" placeholder="e.g. 4.0" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            </x-form-field>
                        @elseif($dynamicType === 'abv')
                            <div class="grid grid-cols-2 gap-3">
                                <x-form-field label="Min ABV %">
                                    <input type="number" wire:model="dynamicMinAbv" step="0.1" min="0" max="30" placeholder="e.g. 5.0" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                                </x-form-field>
                                <x-form-field label="Max ABV %">
                                    <input type="number" wire:model="dynamicMaxAbv" step="0.1" min="0" max="30" placeholder="e.g. 10.0" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                                </x-form-field>
                            </div>
                        @elseif($dynamicType === 'brewery')
                            <x-form-field label="Brewery Name">
                                <input type="text" wire:model="dynamicBrewery" placeholder="e.g. Sierra Nevada" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            </x-form-field>
                        @elseif($dynamicType === 'serving_type')
                            <x-form-field label="Serving Type">
                                <select wire:model="dynamicServingType" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Select a serving type...</option>
                                    @foreach(config('logr.serving_types') as $s)
                                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                        @elseif($dynamicType === 'venue')
                            <x-form-field label="Venue Name">
                                <input type="text" wire:model="dynamicVenue" placeholder="e.g. The Brewpub" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            </x-form-field>
                        @endif

                        <x-form-field label="Description" :optional="true">
                            <textarea wire:model="description" rows="2" placeholder="Add a description..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </x-form-field>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                            @if($dynamicType)
                                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">Create Dynamic Collection</button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
