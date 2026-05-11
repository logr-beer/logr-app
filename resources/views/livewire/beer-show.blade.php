<div>
    <x-flash-message />

    <x-back-link :href="route('beers.index')" label="Back to Library" />

    {{-- Two-column layout: image left, everything else right --}}
    <div class="lg:flex lg:gap-8 lg:items-start">

        {{-- Left column: image only --}}
        <div class="lg:w-1/5 flex-shrink-0 mb-6 lg:mb-0 lg:sticky lg:top-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700">
                    @if($beer->photo_path)
                        <img src="{{ $beer->photo_url }}" alt="{{ $beer->name }}" class="w-full h-full object-cover" />
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <x-application-logo-filled class="w-28 h-28 stroke-current" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right column: 4/5 width --}}
        <div class="lg:w-4/5 min-w-0 space-y-6">

            {{-- Beer Detail Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 md:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ $beer->name }}</h1>
                        @if($beer->brewery)
                            <p class="text-lg text-gray-500 dark:text-gray-400 mt-1">{{ $beer->brewery->name }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            wire:click="toggleFavorite"
                            class="group/fav p-2 rounded-lg {{ $beer->is_favorite ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500' }} transition-colors"
                            title="{{ $beer->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}"
                        >
                            @if($beer->is_favorite)
                                <x-icon name="heart" size="6" :solid="true" />
                            @else
                                <x-icon name="heart" size="6" class="transition-[fill] duration-150 group-hover/fav:fill-red-500 group-hover/fav:duration-[250ms]" />
                            @endif
                        </button>
                        <a
                            href="{{ route('beers.edit', $beer) }}"
                            wire:navigate
                            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-amber-500 transition-colors"
                            title="Edit beer"
                        >
                            <x-icon name="pencil" size="6" />
                        </a>
                        @unless(config('app.demo_mode'))
                            <button
                                wire:click="deleteBeer"
                                wire:confirm="Delete this beer? This will also remove all check-ins, inventory, and collection associations."
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-500 transition-colors"
                                title="Delete beer"
                            >
                                <x-icon name="trash" size="6" />
                            </button>
                        @endunless
                    </div>
                </div>

                @php $tagClass = 'px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded-lg text-sm font-medium'; @endphp
                <div class="flex flex-wrap gap-2 mt-6">
                    @if($beer->abv)
                        <div class="inline-flex items-center gap-1.5 {{ $tagClass }}">
                            <x-icon name="flask" size="4" />
                            {{ $beer->abv }}% ABV
                        </div>
                    @endif
                    @if($beer->ibu)
                        <div class="{{ $tagClass }}">{{ $beer->ibu }} IBU</div>
                    @endif
                    @if($beer->style)
                        @foreach($beer->style as $s)
                            <div class="{{ $tagClass }}">{{ $s }}</div>
                        @endforeach
                    @endif
                    @if($beer->release_year)
                        <div class="{{ $tagClass }}">{{ $beer->release_year }}</div>
                    @endif
                    @if($beer->brewer_master)
                        <div class="{{ $tagClass }}">{{ $beer->brewer_master }}</div>
                    @endif
                    @if($averageRating > 0)
                        <div class="{{ $tagClass }}">
                            {{ number_format($averageRating, 1) }} ★ ({{ $totalCheckins }} {{ Str::plural('check-in', $totalCheckins) }})
                        </div>
                    @endif
                    @if($totalQty > 0)
                        <div class="inline-flex items-center gap-1.5 {{ $tagClass }}">
                            <x-icon name="list" size="4" />
                            {{ $totalQty }} in stock
                        </div>
                    @endif
                </div>

                @if($beer->description)
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed mt-6">{{ $beer->description }}</p>
                @endif

                @php $staticCollections = $beerCollections->where('is_dynamic', false); @endphp
                @if($staticCollections->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($staticCollections as $collection)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-medium">
                                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="hover:underline">{{ $collection->name }}</a>
                                <button wire:click="removeFromCollection({{ $collection->id }})" class="ml-0.5 text-purple-400 hover:text-purple-600 dark:hover:text-purple-300" title="Remove from collection">
                                    <x-icon name="x-mark" size="3" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Inventory & Collections --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Inventory --}}
                <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            <x-icon name="list" size="5" class="inline-block mr-1 text-amber-500" />
                            Inventory
                        </h2>
                        <button @click="open = !open" class="text-sm text-amber-500 hover:text-amber-600 font-medium">
                            <span x-show="!open">+ Add</span>
                            <span x-show="open" x-cloak>Cancel</span>
                        </button>
                    </div>

                    @if($inventoryItems->isNotEmpty())
                        <div class="space-y-2 mb-4">
                            @foreach($inventoryItems as $item)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->storage_location }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">&times; {{ $item->quantity }}</span>
                                        @if($item->purchase_location || $item->date_acquired || $item->is_gift)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                                @if($item->is_gift)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-pink-100 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 rounded text-[10px] font-medium">Gift</span>
                                                @endif
                                                @if($item->purchase_location){{ $item->purchase_location }}@endif
                                                @if($item->purchase_location && $item->date_acquired) · @endif
                                                @if($item->date_acquired){{ $item->date_acquired->format('M j, Y') }}@endif
                                            </div>
                                        @endif
                                    </div>
                                    <button wire:click="removeFromFridge({{ $item->id }})" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="Remove one">
                                        <x-icon name="minus" size="4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">None in storage.</p>
                    @endif

                    <div x-show="open" x-cloak x-transition class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-3">
                            <div x-data="{ locOpen: false }" @click.outside="locOpen = false" class="relative">
                                <label for="storageLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage Location</label>
                                <div class="relative">
                                    <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        wire:model.live.debounce.200ms="storageLocation"
                                        @focus="locOpen = true"
                                        @input="locOpen = true"
                                        type="text"
                                        id="storageLocation"
                                        autocomplete="off"
                                        placeholder="e.g. Fridge, Garage Fridge, Cellar..."
                                        class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                                    />
                                </div>
                                @if($storageLocations->isNotEmpty())
                                    <div x-show="locOpen" x-cloak x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                        @foreach($storageLocations as $loc)
                                            <button
                                                type="button"
                                                wire:click="$set('storageLocation', '{{ $loc }}')"
                                                @click="locOpen = false"
                                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                                            >
                                                <x-icon name="list" size="4" class="text-gray-400 flex-shrink-0" />
                                                <span class="text-gray-900 dark:text-white">{{ $loc }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="addQuantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Qty</label>
                                    <input wire:model="addQuantity" type="number" min="1" id="addQuantity" class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                </div>
                                <div>
                                    <label for="purchaseDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Acquired</label>
                                    <input wire:model="purchaseDate" type="date" id="purchaseDate" class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 dark:[color-scheme:dark]" />
                                </div>
                            </div>
                            <div>
                                <label for="purchaseLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                                <input wire:model="purchaseLocation" type="text" id="purchaseLocation" placeholder="e.g. Total Wine, local brewery, friend..." class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input wire:model="isGift" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This was a gift</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <x-primary-button type="button" wire:click="addToFridge" @click="open = false">Add to Inventory</x-primary-button>
                            @if(!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots')))
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input wire:model="sharePurchaseToDiscord" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                                    <x-icon name="discord" size="4" :solid="true" class="text-amber-400" />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Discord</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Collections --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <x-icon name="collection" size="5" class="inline-block mr-1 text-amber-500" />
                        Collections
                    </h2>

                    @if($beerCollections->isNotEmpty())
                        <div class="space-y-2 mb-4">
                            @foreach($beerCollections as $collection)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('collections.show', $collection) }}" wire:navigate class="text-sm font-medium text-gray-900 dark:text-white hover:text-amber-500 transition-colors">{{ $collection->name }}</a>
                                        @if($collection->is_dynamic)
                                            <span class="text-[10px] font-medium text-purple-500 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-1.5 py-0.5 rounded-full">Dynamic</span>
                                        @endif
                                    </div>
                                    @unless($collection->is_dynamic)
                                        <button wire:click="removeFromCollection({{ $collection->id }})" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="Remove from collection">
                                            <x-icon name="x-mark" size="4" />
                                        </button>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Not in any collection.</p>
                    @endif

                    @if($availableCollections->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <x-custom-select
                                    wireModel="selectedCollectionId"
                                    placeholder="Add to collection..."
                                    size="lg"
                                    :options="collect(['' => 'Add to collection...'])->merge($availableCollections->pluck('name', 'id'))->all()"
                                />
                            </div>
                            <x-primary-button type="button" wire:click="addToCollection">Add</x-primary-button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Check-ins: form + history --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <x-icon name="plus-circle" size="5" class="inline-block mr-1 text-amber-500" />
                    Check-ins
                </h2>

                {{-- Check-in Form --}}
                <form wire:submit="submitCheckin" class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                            <input wire:model="rating" type="number" step="0.5" min="0" max="5" id="rating" placeholder="0 - 5" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            @error('rating') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serving Type</label>
                            <x-custom-select
                                wireModel="serving_type"
                                placeholder="Select..."
                                size="lg"
                                :options="['' => 'Select...', 'draft' => 'Draft', 'bottle' => 'Bottle', 'can' => 'Can', 'crowler' => 'Crowler', 'growler' => 'Growler', 'cask' => 'Cask']"
                            />
                            @error('serving_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div x-data="{ venueOpen: false }" @click.outside="venueOpen = false" class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Venue</label>
                            @if($selectedVenueId)
                                <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                                    <x-icon name="map-pin" size="4" class="text-amber-500 flex-shrink-0" />
                                    <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedVenueName }}</span>
                                    <button type="button" wire:click="clearVenue" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                                        <x-icon name="x-mark" size="4" />
                                    </button>
                                </div>
                            @else
                                <input wire:model.live.debounce.300ms="venueQuery" @focus="venueOpen = true" @input="venueOpen = true" type="text" placeholder="Type a venue name..." class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                                @if(count($venueSuggestions) > 0)
                                    <div x-show="venueOpen" x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                        @foreach($venueSuggestions as $venue)
                                            <button type="button" wire:click="selectVenue({{ $venue->id }})" @click="venueOpen = false" class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2">
                                                <x-icon name="map-pin" size="4" class="text-gray-400 flex-shrink-0" />
                                                <div>
                                                    <span class="text-gray-900 dark:text-white">{{ $venue->name }}</span>
                                                    @if($venue->displayLocation())
                                                        <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">{{ $venue->displayLocation() }}</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                                @if(strlen($venueQuery) >= 2 && count($venueSuggestions) === 0)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No matches — "{{ $venueQuery }}" will be created as a new venue.</p>
                                @endif
                            @endif
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea wire:model="notes" id="notes" rows="1" placeholder="Tasting notes..." class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"></textarea>
                            @error('notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-photo-upload
                                wireModel="checkinPhotos"
                                :multiple="true"
                                label="Photos"
                                error="checkinPhotos.*"
                                :previews="$checkinPhotos"
                                removeAction="removeCheckinPhoto"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-3">
                        @if(!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots')))
                            <label class="inline-flex items-center gap-2 cursor-pointer mr-auto">
                                <input wire:model="shareCheckinToDiscord" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                                <x-icon name="discord" size="4" :solid="true" class="text-amber-400" />
                                <span class="text-sm text-gray-500 dark:text-gray-400">Share to Discord</span>
                            </label>
                        @endif
                        <x-primary-button wire:loading.attr="disabled">
                            <svg wire:loading wire:target="submitCheckin" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Check In
                        </x-primary-button>
                    </div>
                </form>

                {{-- History --}}
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide mb-4">History</h3>

                @if($checkins->count())
                    <div class="space-y-3">
                        @foreach($checkins as $checkin)
                            <a href="{{ route('checkins.edit', $checkin) }}" wire:navigate class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                    @if($checkin->rating !== null)
                                        <span class="text-sm font-bold text-amber-600 dark:text-amber-400">{{ number_format($checkin->rating, 1) }}</span>
                                    @else
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">N/R</span>
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
                                                <x-icon name="map-pin" size="3" />
                                                {{ $checkin->venue?->name ?? $checkin->location }}
                                            </span>
                                        @endif
                                        @if($checkin->untappd_id && str_starts_with($checkin->untappd_id, 'http'))
                                            <span class="text-yellow-500 dark:text-yellow-400" title="Imported from Untappd" onclick="event.preventDefault(); window.open('{{ $checkin->untappd_id }}', '_blank');">
                                                <x-icon name="external-link" size="3.5" />
                                            </span>
                                        @endif
                                    </div>
                                    @if($checkin->notes)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">{{ $checkin->notes }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 group-hover:text-amber-500 mt-1 transition-colors">
                                        {{ $checkin->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-icon name="clock" size="12" class="text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400">No check-ins yet. Be the first!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
