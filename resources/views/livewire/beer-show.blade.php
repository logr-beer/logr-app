<div>
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('beers.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to Library
        </a>
    </div>

    {{-- Beer Detail Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="md:flex">
            {{-- Photo --}}
            <div class="md:w-1/3 lg:w-1/4 flex-shrink-0">
                <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700">
                    @if($beer->photo_path)
                        <img src="{{ Storage::url($beer->photo_path) }}" alt="{{ $beer->name }}" class="w-full h-full object-cover" />
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <svg class="w-20 h-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="p-6 md:p-8 flex-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ $beer->name }}</h1>
                        @if($beer->brewery)
                            <p class="text-lg text-gray-500 dark:text-gray-400 mt-1">{{ $beer->brewery->name }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Favorite toggle --}}
                        <button
                            wire:click="toggleFavorite"
                            class="p-2 rounded-lg {{ $beer->is_favorite ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500' }} transition-colors"
                            title="{{ $beer->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}"
                        >
                            @if($beer->is_favorite)
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                            @endif
                        </button>
                        {{-- Edit link --}}
                        <a
                            href="{{ route('beers.edit', $beer) }}"
                            wire:navigate
                            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-amber-500 transition-colors"
                            title="Edit beer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                        </a>
                        {{-- Delete --}}
                        <button
                            wire:click="deleteBeer"
                            wire:confirm="Delete this beer? This will also remove all check-ins, inventory, and collection associations."
                            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500 transition-colors"
                            title="Delete beer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="flex flex-wrap gap-4 mt-6">
                    @if($beer->style)
                        @foreach($beer->style as $s)
                            <div class="px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded-lg text-sm font-medium">
                                {{ $s }}
                            </div>
                        @endforeach
                    @endif
                    @if($beer->abv)
                        <div class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                            <span class="font-medium">{{ $beer->abv }}%</span> ABV
                        </div>
                    @endif
                    @if($beer->ibu)
                        <div class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                            <span class="font-medium">{{ $beer->ibu }}</span> IBU
                        </div>
                    @endif
                    @if($beer->release_year)
                        <div class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                            <span class="font-medium">{{ $beer->release_year }}</span>
                        </div>
                    @endif
                    @if($beer->brewer_master)
                        <div class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                            <span class="font-medium">{{ $beer->brewer_master }}</span>
                        </div>
                    @endif
                    @if($averageRating > 0)
                        <div class="px-3 py-1.5 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 rounded-lg text-sm font-medium">
                            {{ number_format($averageRating, 1) }} ★ ({{ $totalCheckins }} {{ Str::plural('check-in', $totalCheckins) }})
                        </div>
                    @endif
                    @if($totalQty > 0)
                        <div class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg text-sm font-medium">
                            {{ $totalQty }} in stock
                        </div>
                    @endif
                </div>

                {{-- Description --}}
                @if($beer->description)
                    <div class="mt-6">
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">{{ $beer->description }}</p>
                    </div>
                @endif

                {{-- Collections this beer belongs to --}}
                @if($beerCollections->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($beerCollections as $collection)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-medium">
                                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="hover:underline">{{ $collection->name }}</a>
                                <button wire:click="removeFromCollection({{ $collection->id }})" class="ml-0.5 text-purple-400 hover:text-purple-600 dark:hover:text-purple-300" title="Remove from collection">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Inventory & Collections row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 mt-8">
        {{-- Inventory / Fridges --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 inline-block mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Inventory
                </h2>
                <button @click="open = !open" class="text-sm text-amber-500 hover:text-amber-600 font-medium">
                    <span x-show="!open">+ Add</span>
                    <span x-show="open" x-cloak>Cancel</span>
                </button>
            </div>

            {{-- Current inventory by location --}}
            @if($inventoryItems->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($inventoryItems as $item)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->storage_location }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">× {{ $item->quantity }}</span>
                                @if($item->purchase_location || $item->date_acquired || $item->is_gift)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 flex items-center gap-1.5">
                                        @if($item->is_gift)
                                            <span class="inline-flex items-center px-1.5 py-0.5 bg-pink-100 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 rounded text-[10px] font-medium">Gift</span>
                                        @endif
                                        @if($item->purchase_location){{ $item->purchase_location }}@endif
                                        @if($item->purchase_location && $item->date_acquired) · @endif
                                        @if($item->date_acquired){{ $item->date_acquired->format('M j, Y') }}@endif
                                    </div>
                                @endif
                            </div>
                            <button
                                wire:click="removeFromFridge({{ $item->id }})"
                                class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                                title="Remove one"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Not in any fridge yet.</p>
            @endif

            {{-- Add form --}}
            <div x-show="open" x-cloak x-transition class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label for="storageLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage Location</label>
                        <input
                            wire:model="storageLocation"
                            type="text"
                            id="storageLocation"
                            placeholder="e.g. Fridge, Garage Fridge, Cellar..."
                            class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="addQuantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Qty</label>
                            <input
                                wire:model="addQuantity"
                                type="number"
                                min="1"
                                id="addQuantity"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                        <div>
                            <label for="purchaseDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Acquired</label>
                            <input
                                wire:model="purchaseDate"
                                type="date"
                                id="purchaseDate"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                    </div>
                    <div>
                        <label for="purchaseLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                        <input
                            wire:model="purchaseLocation"
                            type="text"
                            id="purchaseLocation"
                            placeholder="e.g. Total Wine, local brewery, friend..."
                            class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                wire:model="isGift"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This was a gift</span>
                        </label>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <button
                        wire:click="addToFridge"
                        @click="open = false"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors"
                    >
                        Add to Inventory
                    </button>

                    @if(!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots')))
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                wire:model="sharePurchaseToDiscord"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700"
                            />
                            <svg class="w-4 h-4 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Discord</span>
                        </label>
                    @endif
                </div>
            </div>
        </div>

        {{-- Collections --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <svg class="w-5 h-5 inline-block mr-1 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-1.243 1.007-2.25 2.25-2.25h13.5"/></svg>
                Collections
            </h2>

            @if($beerCollections->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($beerCollections as $collection)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <a href="{{ route('collections.show', $collection) }}" wire:navigate class="text-sm font-medium text-gray-900 dark:text-white hover:text-amber-500 transition-colors">
                                {{ $collection->name }}
                            </a>
                            <button
                                wire:click="removeFromCollection({{ $collection->id }})"
                                class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                                title="Remove from collection"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Not in any collection.</p>
            @endif

            @if($availableCollections->isNotEmpty())
                <div class="flex items-center gap-2">
                    <select
                        wire:model.live="selectedCollectionId"
                        class="flex-1 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option value="">Add to collection...</option>
                        @foreach($availableCollections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                        @endforeach
                    </select>
                    <button
                        wire:click="addToCollection"
                        class="px-4 py-2 bg-purple-500 text-white text-sm font-medium rounded-lg hover:bg-purple-600 transition-colors"
                    >
                        Add
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Check-in Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
            <svg class="w-5 h-5 inline-block mr-1 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Check In
        </h2>

        <form wire:submit="submitCheckin">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Rating --}}
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                    <input
                        wire:model="rating"
                        type="number"
                        step="0.5"
                        min="0"
                        max="5"
                        id="rating"
                        placeholder="0 - 5"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                    />
                    @error('rating') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Serving Type --}}
                <div>
                    <label for="serving_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serving Type</label>
                    <select
                        wire:model="serving_type"
                        id="serving_type"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option value="">Select...</option>
                        <option value="draft">Draft</option>
                        <option value="bottle">Bottle</option>
                        <option value="can">Can</option>
                        <option value="crowler">Crowler</option>
                        <option value="growler">Growler</option>
                        <option value="cask">Cask</option>
                    </select>
                    @error('serving_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Venue --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Venue</label>

                    @if($selectedVenueId)
                        {{-- Selected venue tag --}}
                        <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedVenueName }}</span>
                            <button type="button" wire:click="clearVenue" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @else
                        {{-- Search input --}}
                        <input
                            wire:model.live.debounce.300ms="venueQuery"
                            @focus="open = true"
                            @input="open = true"
                            type="text"
                            placeholder="Type a venue name..."
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />

                        {{-- Autocomplete dropdown --}}
                        @if(count($venueSuggestions) > 0)
                            <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($venueSuggestions as $venue)
                                    <button
                                        type="button"
                                        wire:click="selectVenue({{ $venue->id }})"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                        <div>
                                            <span class="text-gray-900 dark:text-white">{{ $venue->name }}</span>
                                            @if($venue->displayLocation())
                                                <span class="text-gray-400 dark:text-gray-500 text-xs ml-1">{{ $venue->displayLocation() }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if(strlen($venueQuery) >= 2 && count($venueSuggestions) === 0)
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">No matches — "{{ $venueQuery }}" will be created as a new venue.</p>
                        @endif
                    @endif
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea
                        wire:model="notes"
                        id="notes"
                        rows="1"
                        placeholder="Tasting notes..."
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                    ></textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Photos --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photos</label>
                    <input
                        wire:model="checkinPhotos"
                        type="file"
                        multiple
                        accept="image/*"
                        class="w-full text-sm text-gray-500 dark:text-gray-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-amber-50 file:text-amber-700
                            dark:file:bg-amber-900/20 dark:file:text-amber-400
                            hover:file:bg-amber-100 dark:hover:file:bg-amber-900/30
                            file:cursor-pointer file:transition-colors"
                    />
                    @error('checkinPhotos.*') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

                    @if($checkinPhotos)
                        <div class="flex flex-wrap gap-3 mt-3">
                            @foreach($checkinPhotos as $index => $photo)
                                <div class="relative group">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                                    <button
                                        type="button"
                                        wire:click="removeCheckinPhoto({{ $index }})"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="checkinPhotos" class="mt-2 text-sm text-amber-500">
                        Uploading photos...
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <svg wire:loading wire:target="submitCheckin" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Check In
                </button>

                @if(!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots')))
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            wire:model="shareCheckinToDiscord"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700"
                        />
                        <svg class="w-4 h-4 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Share to Discord</span>
                    </label>
                @endif
            </div>
        </form>
    </div>

    {{-- Check-in History --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
            <svg class="w-5 h-5 inline-block mr-1 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Check-in History
        </h2>

        @if($checkins->count())
            <div class="space-y-4">
                @foreach($checkins as $checkin)
                    <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                            @if($checkin->rating !== null)
                                <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($checkin->rating, 1) }}</span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">N/R</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $checkin->user?->name ?? 'Unknown' }}</span>
                                @if($checkin->serving_type)
                                    <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded text-xs capitalize">{{ $checkin->serving_type }}</span>
                                @endif
                                @if($checkin->venue || $checkin->location)
                                    <span class="text-gray-400 text-xs flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                        {{ $checkin->venue?->name ?? $checkin->location }}
                                    </span>
                                @endif
                            </div>
                            @if($checkin->notes)
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $checkin->notes }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1 flex items-center gap-2">
                                <a href="{{ route('checkins.edit', $checkin) }}" wire:navigate class="hover:text-amber-500 transition-colors">
                                    {{ $checkin->created_at->diffForHumans() }}
                                </a>
                                @if($checkin->untappd_id && str_starts_with($checkin->untappd_id, 'http'))
                                    <a href="{{ $checkin->untappd_id }}" target="_blank" rel="noopener" class="text-amber-400 hover:text-amber-500" title="View on Untappd">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    </a>
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <p class="text-gray-500 dark:text-gray-400">No check-ins yet. Be the first!</p>
            </div>
        @endif
    </div>
</div>
