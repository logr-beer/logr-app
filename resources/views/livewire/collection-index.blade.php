<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Collections</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <button wire:click="$set('collectionFilter', 'all')" class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $collectionFilter === 'all' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">All</button>
                <button wire:click="$set('collectionFilter', 'smart')" class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $collectionFilter === 'smart' ? 'bg-purple-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Smart</button>
            </div>

            {{-- Search (full width on mobile) --}}
            <div class="relative w-full sm:w-56 sm:ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search collections..." class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500" />
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <x-sort-control :options="['newest' => 'Newest', 'name' => 'Name', 'count' => 'Beers']" />

                {{-- + New button --}}
                <button
                    wire:click="$set('showCreateModal', true)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New
                </button>
            </div>
        </div>
    </div>

    {{-- Collections Grid --}}
    @if($collections->isNotEmpty() && $collectionFilter === 'all')
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Your Collections</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <div class="aspect-square bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                        @if($collection->cover_path)
                            <img src="{{ Storage::url($collection->cover_path) }}" alt="{{ $collection->name }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-1.243 1.007-2.25 2.25-2.25h13.5"/></svg>
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

    {{-- Smart Collections --}}
    @if($dynamicCollections->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
            Smart Collections
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($dynamicCollections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <div class="aspect-square bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                        <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
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
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-1.243 1.007-2.25 2.25-2.25h13.5"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $search ? 'No collections found' : 'No collections yet' }}</h3>
            <p class="text-gray-500 dark:text-gray-400">{{ $search ? 'Try a different search term.' : 'Create your first collection to start organizing your beers.' }}</p>
        </div>
    @endif

    {{-- Create Collection Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showCreateModal', false)">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">New Collection</h3>

                {{-- Tabs --}}
                <div class="flex items-center gap-1 mb-5">
                    <button
                        wire:click="$set('createTab', 'collection')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $createTab === 'collection' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >Collection</button>
                    <button
                        wire:click="$set('createTab', 'smart')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $createTab === 'smart' ? 'bg-purple-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >Smart</button>
                </div>

                {{-- Collection Form --}}
                @if($createTab === 'collection')
                    <form wire:submit="createCollection" class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input type="text" id="name" wire:model="name" placeholder="e.g. Summer Favorites, IPAs to Try..." class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                            <textarea id="description" wire:model="description" rows="2" placeholder="What's this collection about?" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                            @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">Create Collection</button>
                        </div>
                    </form>
                @endif

                {{-- Smart Collection Form --}}
                @if($createTab === 'smart')
                    <form wire:submit="createDynamicCollection" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select wire:model.live="dynamicType" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Select a type...</option>
                                <option value="style">By Style</option>
                                <option value="rating">By Minimum Rating</option>
                                <option value="favorites">Favorites</option>
                                <option value="oldest_in_stock">Oldest in Stock</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                @if($dynamicType === 'style')
                                    Style
                                @elseif($dynamicType === 'rating')
                                    Minimum Rating
                                @else
                                    Options
                                @endif
                            </label>
                            @if($dynamicType === 'style')
                                <select wire:model="dynamicStyle" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Select a style...</option>
                                    @foreach(['IPA', 'Double IPA', 'Hazy IPA', 'Pale Ale', 'Stout', 'Imperial Stout', 'Porter', 'Lager', 'Pilsner', 'Sour', 'Wheat Beer', 'Saison', 'Belgian Tripel', 'Barleywine', 'Brown Ale', 'Amber Ale', 'Cider'] as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                            @elseif($dynamicType === 'rating')
                                <input type="number" wire:model="dynamicMinRating" step="0.5" min="1" max="5" placeholder="e.g. 4.0" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                            @else
                                <input type="text" disabled placeholder="Select a type above..." class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm bg-gray-50 dark:bg-gray-600 text-gray-400 cursor-not-allowed" />
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                            <textarea wire:model="description" rows="2" placeholder="Add a description..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                            @if($dynamicType)
                                <button type="submit" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium rounded-lg transition-colors">Create Smart Collection</button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
