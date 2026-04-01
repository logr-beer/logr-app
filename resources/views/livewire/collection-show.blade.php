<div>
    {{-- Collection Header --}}
    <div class="mb-8">
        @if($editing)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Edit Collection</h2>
                <form wire:submit="updateCollection" class="space-y-4">
                    <div>
                        <label for="editName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" id="editName" wire:model="editName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                        @error('editName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="editDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea id="editDescription" wire:model="editDescription" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('editDescription') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">Save</button>
                        <button type="button" wire:click="cancelEditing" class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm font-medium hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('collections.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex-1">{{ $collection->name }}</h1>
                <div class="flex items-center gap-2">
                    @if($isDynamic)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-lg text-xs font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                            Smart
                        </span>
                    @endif
                    <button wire:click="startEditing" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-amber-500 transition-colors" title="Edit collection">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                    </button>
                    <button wire:click="deleteCollection" wire:confirm="Delete this collection? The beers won't be deleted." class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500 transition-colors" title="Delete collection">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </div>
            </div>
            @if($collection->description)
                <p class="text-gray-500 dark:text-gray-400 ml-8">{{ $collection->description }}</p>
            @endif
        @endif
    </div>

    {{-- Add Beer Search (manual collections only) --}}
    @if(!$isDynamic)
    <div class="mb-8 bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Beer to Collection
        </h2>
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="addBeerSearch"
                wire:keyup="searchBeers"
                placeholder="Search your beers..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
            >
        </div>

        @if(count($addBeerResults) > 0)
            <ul class="mt-3 divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                @foreach($addBeerResults as $result)
                    <li class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $result['name'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $result['brewery'] }}</p>
                        </div>
                        <button
                            wire:click="addBeer({{ $result['id'] }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif(strlen($addBeerSearch) >= 2)
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No beers found matching your search.</p>
        @endif
    </div>
    @endif

    {{-- Beer Grid --}}
    <div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Beers in this Collection <span class="text-sm font-normal text-gray-400">({{ $beers->count() }})</span></h2>

        @if($beers->isEmpty())
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                <p class="text-gray-500 dark:text-gray-400">No beers in this collection yet. Search above to add some.</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($beers as $beer)
                    <div class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg transition-shadow">
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate>
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                @if($beer->photo_path)
                                    <img src="{{ Storage::url($beer->photo_path) }}" alt="{{ $beer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                        <x-application-logo class="w-12 h-12 stroke-current" />
                                    </div>
                                @endif
                            </div>
                        </a>
                        {{-- Remove button (manual collections only) --}}
                        @if(!$isDynamic)
                        <button
                            wire:click="removeBeer({{ $beer->id }})"
                            wire:confirm="Remove this beer from the collection?"
                            class="absolute top-2 right-2 p-1.5 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-all"
                            title="Remove from collection"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                        @endif
                        <div class="p-3">
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $beer->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
