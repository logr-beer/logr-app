<div>
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ $isEditing ? 'Edit Beer' : 'Add New Beer' }}
        </h1>

        <x-flash-message />

        {{-- Beer Search (Untappd / catalog.beer) --}}
        @if($hasApiKey && !$isEditing)
        <div x-data="{ open: @entangle('showBeerDropdown') }" @click.outside="open = false" class="relative mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search to auto-fill</label>

                @if($selectedSearchBeer)
                    <div class="flex items-center gap-2 px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                        <x-icon name="flask" size="4" class="text-amber-500 flex-shrink-0" />
                        <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedSearchBeer }}</span>
                        <button type="button" wire:click="clearSearchBeer" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                            <x-icon name="x-mark" size="4" />
                        </button>
                    </div>
                @else
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                wire:model.live.debounce.400ms="beerSearch"
                                @focus="if ($wire.beerSearch.length >= 2) open = true"
                                type="text"
                                id="beer_search"
                                autocomplete="off"
                                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                                placeholder="Search for a beer to import (e.g. Pliny the Elder)..."
                            />
                        </div>
                        @if(count($availableSources) > 1)
                            <select wire:model.live="beerSearchSource" class="w-auto py-2.5 pl-3 pr-8 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-xs text-gray-600 dark:text-gray-300 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">All Sources</option>
                                @foreach($availableSources as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endif

                <div x-show="open" x-cloak class="absolute z-50 left-4 right-4 mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                    <div wire:loading.delay wire:target="beerSearch" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        Searching...
                    </div>

                    <div wire:loading.remove wire:target="beerSearch">
                        @if(count($beerResults) > 0)
                            @foreach($beerResults as $result)
                                @php $resultKey = $result['bid'] ?? $result['id']; @endphp
                                @php $breweryName = $result['brewery_name'] ?? $result['brewery']['name'] ?? $result['brewer']['name'] ?? null; @endphp
                                <button type="button" wire:click="importBeer('{{ $resultKey }}')" class="w-full text-left px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white block truncate">{{ $result['name'] }}</span>
                                            @if($breweryName)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $breweryName }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            @if($result['style'] ?? null)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $result['style'] }}</span>
                                            @endif
                                            @if($result['abv'] ?? null)
                                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ $result['abv'] }}%</span>
                                            @endif
                                            @if(isset($result['rating']) && $result['rating'])
                                                <span class="text-xs text-yellow-500">{{ number_format($result['rating'], 1) }} ★</span>
                                            @endif
                                            @if($result['_source'] ?? null)
                                                <x-api-source-badge :source="$result['_source']" />
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        @else
                            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No beers found.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form wire:submit="save" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Name --}}
                <x-form-field label="Beer Name" name="name" :required="true" span="full">
                    <input
                        wire:model="name"
                        type="text"
                        id="name"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Enter beer name"
                    />
                </x-form-field>

                {{-- Brewery --}}
                <div x-data="{ open: @entangle('showBreweryDropdown') }" @click.outside="open = false" class="relative md:col-span-2">
                    <label for="brewery_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brewery</label>
                    <div class="relative">
                        <input
                            wire:model.live.debounce.300ms="brewerySearch"
                            @focus="if ($wire.brewerySearch.length >= 2) open = true"
                            type="text"
                            id="brewery_search"
                            autocomplete="off"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 {{ $brewery_id ? 'pr-8' : '' }}"
                            placeholder="Search breweries..."
                        />
                        @if($brewery_id)
                            <button type="button" wire:click="clearBrewery" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <x-icon name="x-mark" size="4" />
                            </button>
                        @endif
                    </div>

                    {{-- Dropdown results --}}
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                        <div wire:loading.delay wire:target="brewerySearch" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            Searching...
                        </div>

                        <div wire:loading.remove wire:target="brewerySearch">
                            @if(count($breweryResults['local']) > 0)
                                <div class="px-3 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Your Breweries</div>
                                @foreach($breweryResults['local'] as $brewery)
                                    <button type="button" wire:click="selectBrewery({{ $brewery['id'] }})" class="w-full text-left px-4 py-2.5 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $brewery['name'] }}</span>
                                        @if(($brewery['city'] ?? null) || ($brewery['state'] ?? null))
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ collect([$brewery['city'] ?? null, $brewery['state'] ?? null])->filter()->join(', ') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            @endif

                            @if(count($breweryResults['api']) > 0)
                                <div class="px-3 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider {{ count($breweryResults['local']) > 0 ? 'border-t border-gray-100 dark:border-gray-600' : '' }}">Search Results</div>
                                @foreach($breweryResults['api'] as $apiBrewery)
                                    <button type="button" wire:click="importAndSelectBrewery('{{ $apiBrewery['id'] }}')" class="w-full text-left px-4 py-2.5 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $apiBrewery['name'] }}</span>
                                        @if(($apiBrewery['city'] ?? null) || ($apiBrewery['state'] ?? null))
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ collect([$apiBrewery['city'] ?? null, $apiBrewery['state'] ?? null])->filter()->join(', ') }}</span>
                                        @endif
                                        @if($apiBrewery['_source'] ?? null)
                                            <x-api-source-badge :source="$apiBrewery['_source']" class="ml-1" />
                                        @endif
                                    </button>
                                @endforeach
                            @endif

                            @if(count($breweryResults['local']) === 0 && count($breweryResults['api']) === 0)
                                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No breweries found.</div>
                            @endif
                        </div>
                    </div>

                    @error('brewery_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Style (multi-select dropdown) --}}
                <div class="md:col-span-2 relative" x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Style</label>

                    {{-- Trigger button --}}
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-left focus:ring-amber-500 focus:border-amber-500"
                    >
                        @if(count($style) > 0)
                            <div class="flex flex-wrap gap-1 flex-1 min-w-0">
                                @foreach($style as $s)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-600/10 text-amber-600 dark:text-amber-400 border border-amber-400/50 dark:border-amber-500/40 rounded-full text-xs font-medium">
                                        {{ $s }}
                                        <span
                                            @click.stop="$wire.set('style', {{ json_encode(array_values(array_diff($style, [$s]))) }})"
                                            class="cursor-pointer text-amber-400 hover:text-amber-600 dark:hover:text-amber-300"
                                        >
                                            <x-icon name="x-mark" size="3" />
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">Select styles...</span>
                        @endif
                        <x-icon name="chevron-down" size="4" class="text-gray-400 flex-shrink-0 ml-2 transition-transform" ::class="{ 'rotate-180': open }" />
                    </button>

                    {{-- Dropdown panel --}}
                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-72 overflow-y-auto"
                    >
                        @if(count($style) > 0)
                            <div class="px-3 pt-2 pb-1">
                                <h4 class="text-[10px] font-semibold text-amber-500 dark:text-amber-400 uppercase tracking-wider">Selected</h4>
                            </div>
                            @foreach($style as $s)
                                <label class="flex items-center gap-2 px-3 py-1.5 cursor-pointer bg-amber-50/50 dark:bg-amber-900/10 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                                    <input
                                        type="checkbox"
                                        value="{{ $s }}"
                                        wire:model.live="style"
                                        checked
                                        class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                                    />
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $s }}</span>
                                </label>
                            @endforeach
                            <div class="border-b border-gray-200 dark:border-gray-700 my-1"></div>
                        @endif

                        @foreach($styles as $category => $categoryStyles)
                            @php $unselected = array_diff($categoryStyles, $style); @endphp
                            @if(count($unselected) > 0)
                                <div class="px-3 pt-2 pb-1">
                                    <h4 class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $category }}</h4>
                                </div>
                                @foreach($unselected as $s)
                                    <label class="flex items-center gap-2 px-3 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <input
                                            type="checkbox"
                                            value="{{ $s }}"
                                            wire:model.live="style"
                                            class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                                        />
                                        <span class="text-sm text-gray-900 dark:text-gray-200">{{ $s }}</span>
                                    </label>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                    @error('style') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- ABV --}}
                <div>
                    <label for="abv" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ABV %</label>
                    <input
                        wire:model="abv"
                        type="number"
                        step="0.1"
                        min="0"
                        max="100"
                        id="abv"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="e.g. 6.5"
                    />
                    @error('abv') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- IBU --}}
                <div>
                    <label for="ibu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IBU</label>
                    <input
                        wire:model="ibu"
                        type="number"
                        min="0"
                        max="999"
                        id="ibu"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="e.g. 65"
                    />
                    @error('ibu') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Release Year --}}
                <div>
                    <label for="release_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Release Year</label>
                    <input
                        wire:model="release_year"
                        type="number"
                        min="1800"
                        max="{{ date('Y') + 1 }}"
                        id="release_year"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="e.g. {{ date('Y') }}"
                    />
                    @error('release_year') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Brewer / Master --}}
                <div>
                    <label for="brewer_master" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brewer / Master</label>
                    <input
                        wire:model="brewer_master"
                        type="text"
                        id="brewer_master"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Brewmaster name"
                    />
                    @error('brewer_master') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea
                        wire:model="description"
                        id="description"
                        rows="4"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Describe this beer..."
                    ></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Photo Upload --}}
                <div class="md:col-span-2">
                    <x-photo-upload
                        wireModel="photo"
                        label="Photo"
                        hint="JPG, PNG or WebP. Max 10MB."
                        error="photo"
                        :previews="$photo ? [$photo] : null"
                        :existingPhotos="$beer && $beer->photo_path ? [$beer->photo_path] : null"
                    />
                </div>
            </div>

            {{-- Check-in (Add form only) --}}
            @if(!$isEditing)
            <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-data="{ showCheckin: @entangle('addCheckin') }">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        x-model="showCheckin"
                        wire:model.live="addCheckin"
                        class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Check in this beer</span>
                </label>

                <div x-show="showCheckin" x-cloak x-transition class="mt-4 space-y-4">
                    {{-- Venue --}}
                    <x-location-autocomplete label="Venue" prefix="venue" model="App\\Models\\Venue" :selectedId="$selectedVenueId" :selectedName="$selectedVenueName" :suggestions="$venueSuggestions" :apiResults="$venueApiResults" icon="map-pin" placeholder="e.g. Hop Lot Suttons Bay MI..." />

                    {{-- Rating & Serving --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                            <input
                                wire:model="checkinRating"
                                type="number"
                                step="0.01"
                                min="0"
                                max="5"
                                placeholder="0 - 5 (optional)"
                                class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serving</label>
                            <x-custom-select
                                wireModel="checkinServingType"
                                placeholder="—"
                                size="lg"
                                :options="['' => '—', 'draft' => 'Draft', 'bottle' => 'Bottle', 'can' => 'Can', 'crowler' => 'Crowler', 'growler' => 'Growler', 'cask' => 'Cask']"
                            />
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea
                            wire:model="checkinNotes"
                            rows="2"
                            placeholder="Tasting notes..."
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        ></textarea>
                    </div>

                    {{-- Photos --}}
                    @if(($beer && $beer->photo_path) || $photo)
                        <div class="space-y-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    wire:model.live="useBeerPhoto"
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                                />
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Use beer photo</span>
                            </label>
                            @if(!$useBeerPhoto)
                                <x-photo-upload
                                    wireModel="checkinPhotos"
                                    :multiple="true"
                                    label="Photos"
                                    hint="Up to 10MB per photo. Multiple photos allowed."
                                    error="checkinPhotos.*"
                                    :previews="$checkinPhotos"
                                />
                            @endif
                        </div>
                    @else
                        <x-photo-upload
                            wireModel="checkinPhotos"
                            :multiple="true"
                            label="Photos"
                            hint="Up to 10MB per photo. Multiple photos allowed."
                            error="checkinPhotos.*"
                            :previews="$checkinPhotos"
                        />
                    @endif

                    @if(count($shareTargets) > 0)
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Share to:</span>
                            @foreach($shareTargets as $i => $target)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input wire:model="shareTargets.{{ $i }}.enabled" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                                    <x-icon name="{{ $target['icon'] }}" size="3.5" :solid="true" class="text-amber-400" />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $target['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Inventory (Add form only) --}}
            @if(!$isEditing)
            <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-data="{ showInventory: @entangle('addToInventory') }">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        x-model="showInventory"
                        wire:model.live="addToInventory"
                        class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Add to inventory</span>
                </label>

                <div x-show="showInventory" x-cloak x-transition class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="storageLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage Location</label>
                        <input
                            wire:model="storageLocation"
                            type="text"
                            id="storageLocation"
                            placeholder="e.g. Fridge, Garage Fridge, Cellar..."
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />
                    </div>
                    <div>
                        <label for="addQuantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Qty</label>
                        <input
                            wire:model="addQuantity"
                            type="number"
                            min="1"
                            id="addQuantity"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />
                    </div>
                    <x-location-autocomplete label="Store" prefix="store" model="App\\Models\\Store" :selectedId="$selectedStoreId" :selectedName="$selectedStoreName" :suggestions="$storeSuggestions" :apiResults="$storeApiResults" icon="building" placeholder="e.g. Total Wine Chicago IL..." />
                    <div>
                        <label for="purchaseDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Acquired</label>
                        <input
                            wire:model="purchaseDate"
                            type="date"
                            id="purchaseDate"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 dark:[color-scheme:dark]"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                wire:model="isGift"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This was a gift</span>
                        </label>
                    </div>
                    @if(count($inventoryShareTargets) > 0)
                        <div class="md:col-span-2 flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Share to:</span>
                            @foreach($inventoryShareTargets as $i => $target)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input wire:model="inventoryShareTargets.{{ $i }}.enabled" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                                    <x-icon name="{{ $target['icon'] }}" size="3.5" :solid="true" class="text-amber-400" />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $target['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                @if($isEditing && !config('app.demo_mode'))
                    <button
                        type="button"
                        wire:click="deleteBeer"
                        wire:confirm="Are you sure you want to delete this beer? All check-ins, inventory, and collection links will be removed."
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-500/40 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <x-icon name="trash" size="4" />
                        Delete
                    </button>
                @endif
                <div class="flex-1"></div>
                <a
                    href="{{ $isEditing ? route('beers.show', $beer) : route('beers.index') }}"
                    wire:navigate
                    class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    Cancel
                </a>
                <x-primary-button size="lg" wire:loading.attr="disabled">
                    <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg wire:loading wire:target="photo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    {{ $isEditing ? 'Update Beer' : 'Add Beer' }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
