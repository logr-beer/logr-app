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
                        <x-primary-button>Save</x-primary-button>
                        <button type="button" wire:click="cancelEditing" class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm font-medium hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('collections.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <x-icon name="arrow-left" size="5" />
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex-1">{{ $collection->name }}</h1>
                <div class="flex items-center gap-2">
                    @if($isDynamic)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-lg text-xs font-medium">
                            <x-icon name="sparkle" size="3.5" />
                            Dynamic
                        </span>
                    @endif
                    <button wire:click="startEditing" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-amber-500 transition-colors" title="Edit collection">
                        <x-icon name="pencil" size="5" />
                    </button>
                    @unless(config('app.demo_mode'))
                        <button wire:click="deleteCollection" wire:confirm="Delete this collection? The beers won't be deleted." class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500 transition-colors" title="Delete collection">
                            <x-icon name="trash" size="5" />
                        </button>
                    @endunless
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
            <x-icon name="plus" size="4" class="text-amber-500" />
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
                        <x-primary-button type="button" wire:click="addBeer({{ $result['id'] }})" size="sm">
                            <x-icon name="plus" size="4" /> Add
                        </x-primary-button>
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
                <x-icon name="flask" size="12" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No beers in this collection yet. Search above to add some.</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($beers as $beer)
                    <div class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg transition-shadow">
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate>
                            <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                @if($beer->photo_path)
                                    <img src="{{ $beer->photo_url }}" alt="{{ $beer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                                        <x-application-logo-filled class="w-16 h-16 stroke-current" />
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
                            <x-icon name="x-mark" size="4" />
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
