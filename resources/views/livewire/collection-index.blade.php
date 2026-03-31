<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collections</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Organize your beers into curated and smart collections.</p>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-3">
            {{-- Filter toggle --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <button
                    wire:click="$set('collectionFilter', 'all')"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $collectionFilter === 'all' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >All</button>
                <button
                    wire:click="$set('collectionFilter', 'regular')"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $collectionFilter === 'regular' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >Collections</button>
                <button
                    wire:click="$set('collectionFilter', 'smart')"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $collectionFilter === 'smart' ? 'bg-purple-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >Smart</button>
            </div>

            {{-- Search --}}
            <div class="relative flex-1 min-w-0 max-w-[50%]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search collections..." class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500" />
            </div>
        </div>
    </div>

    {{-- Create Actions --}}
    <div class="mb-8 grid grid-cols-2 sm:grid-cols-4 gap-3" x-data="{ showNewForm: false, showSmartForm: false }">
        {{-- New Collection Button --}}
        <button
            @click="showNewForm = !showNewForm; showSmartForm = false"
            class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md hover:scale-[1.02] transition-all text-center"
            :class="showNewForm ? 'ring-2 ring-amber-500' : ''"
        >
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">New Collection</span>
        </button>

        {{-- Smart Collection Button --}}
        <button
            @click="showSmartForm = !showSmartForm; showNewForm = false"
            class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md hover:scale-[1.02] transition-all text-center"
            :class="showSmartForm ? 'ring-2 ring-purple-500' : ''"
        >
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Smart Collection</span>
        </button>

        {{-- New Collection Form (expandable) --}}
        <div x-show="showNewForm" x-collapse class="col-span-2 sm:col-span-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <form wire:submit="createCollection" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input
                            type="text"
                            id="name"
                            wire:model="name"
                            placeholder="e.g. Summer Favorites, IPAs to Try..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                        @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="2"
                            placeholder="What's this collection about?"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        ></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Create Collection
                    </button>
                </form>
            </div>
        </div>

        {{-- Smart Collection Form (expandable) --}}
        <div x-show="showSmartForm" x-collapse class="col-span-2 sm:col-span-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <form wire:submit="createDynamicCollection" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select
                            wire:model.live="dynamicType"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">Select a smart collection type...</option>
                            <option value="style">By Style</option>
                            <option value="rating">By Minimum Rating</option>
                            <option value="favorites">Favorites</option>
                            <option value="oldest_in_stock">Oldest in Stock</option>
                        </select>
                    </div>

                    @if($dynamicType === 'style')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Style</label>
                            <select wire:model="dynamicStyle" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Select a style...</option>
                                @foreach(['IPA', 'Double IPA', 'Hazy IPA', 'Pale Ale', 'Stout', 'Imperial Stout', 'Porter', 'Lager', 'Pilsner', 'Sour', 'Wheat Beer', 'Saison', 'Belgian Tripel', 'Barleywine', 'Brown Ale', 'Amber Ale', 'Cider'] as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($dynamicType === 'rating')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Minimum Rating</label>
                            <input
                                type="number"
                                wire:model="dynamicMinRating"
                                step="0.5"
                                min="1"
                                max="5"
                                placeholder="e.g. 4.0"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>
                    @endif

                    @if($dynamicType)
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 text-white text-sm font-medium rounded-lg hover:bg-purple-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Create Smart Collection
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Manual Collections Grid --}}
    @if($collectionFilter !== 'smart')
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Your Collections</h2>

        @if($collections->isEmpty())
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-1.243 1.007-2.25 2.25-2.25h13.5"/></svg>
                <p class="text-gray-500 dark:text-gray-400">{{ $search ? 'No collections match your search.' : 'No collections yet. Create one above to get started.' }}</p>
            </div>
        @else
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
        @endif
    </div>
    @endif

    {{-- Dynamic / Smart Collections --}}
    @if($collectionFilter !== 'regular')
        @if($dynamicCollections->isNotEmpty())
        <div>
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
        @elseif($collectionFilter === 'smart')
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <p class="text-gray-500 dark:text-gray-400">{{ $search ? 'No smart collections match your search.' : 'No smart collections yet. Create one above to get started.' }}</p>
            </div>
        @endif
    @endif
</div>
