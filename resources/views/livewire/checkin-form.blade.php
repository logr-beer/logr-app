<div>
    <x-back-link :href="route('checkins.index')" label="Back to Check-ins" />

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 max-w-4xl mx-auto">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
            @if($checkinId)
                <x-icon name="pencil" size="5" class="inline-block mr-1 text-amber-500" />
                Edit Check-in
            @else
                <x-icon name="plus-circle" size="5" class="inline-block mr-1 text-amber-500" />
                New Check-in
            @endif
        </h1>

        <form wire:submit="submitCheckin">
            <div class="space-y-4">
                {{-- Beer Search --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beer *</label>

                    @if($selectedBeerId)
                        <div class="flex items-center gap-2 px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                            <x-icon name="flask" size="4" class="text-amber-500 flex-shrink-0" />
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedBeerName }}</span>
                            <button type="button" wire:click="clearBeer" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                                <x-icon name="x-mark" size="4" />
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                wire:model.live.debounce.300ms="beerQuery"
                                @focus="open = true"
                                @input="open = true"
                                type="text"
                                autocomplete="off"
                                placeholder="Search for a beer..."
                                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        @if(count($beerSuggestions) > 0 || count($apiResults) > 0)
                            <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                {{-- Local results --}}
                                @foreach($beerSuggestions as $beer)
                                    <button
                                        type="button"
                                        wire:click="selectBeer({{ $beer->id }})"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-100 hover:text-amber-800 dark:hover:bg-amber-900/40 dark:hover:text-amber-300 transition-colors flex items-center gap-3"
                                    >
                                        @if($beer->photo_path)
                                            <img src="{{ $beer->photo_url }}" alt="" class="w-8 h-8 rounded object-cover flex-shrink-0" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                <x-application-logo-filled class="w-6 h-6 stroke-current text-gray-400" />
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <span class="text-gray-900 dark:text-white font-medium">{{ $beer->name }}</span>
                                            @if($beer->brewery)
                                                <span class="text-gray-500 dark:text-gray-400 text-xs block">{{ $beer->brewery->name }}</span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">In Library</span>
                                    </button>
                                @endforeach

                                {{-- API results --}}
                                @if(count($apiResults) > 0)
                                    @if(count($beerSuggestions) > 0)
                                        <div class="border-t border-gray-200 dark:border-gray-600 px-4 py-1.5">
                                            <span class="text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Search Results</span>
                                        </div>
                                    @endif
                                    @foreach($apiResults as $result)
                                        @php $resultKey = $result['bid'] ?? $result['id']; @endphp
                                        @php $breweryName = $result['brewery_name'] ?? $result['brewery']['name'] ?? $result['brewer']['name'] ?? null; @endphp
                                        <button
                                            type="button"
                                            wire:click="importAndSelectBeer('{{ $resultKey }}')"
                                            @click="open = false"
                                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-100 hover:text-amber-800 dark:hover:bg-amber-900/40 dark:hover:text-amber-300 transition-colors flex items-center gap-3"
                                        >
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                <x-application-logo-filled class="w-6 h-6 stroke-current text-gray-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-gray-900 dark:text-white font-medium">{{ $result['name'] }}</span>
                                                @if($breweryName)
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs block">{{ $breweryName }}</span>
                                                @endif
                                            </div>
                                            @if($result['abv'] ?? null)
                                                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ $result['abv'] }}%</span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif

                                <div wire:loading wire:target="beerQuery" class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">
                                    Searching...
                                </div>
                            </div>
                        @elseif(strlen($beerQuery) >= 2)
                            <div class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg" x-show="open">
                                <div wire:loading wire:target="beerQuery" class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">
                                    Searching...
                                </div>
                                <div wire:loading.remove wire:target="beerQuery" class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">
                                    No beers found matching "{{ $beerQuery }}".
                                </div>
                            </div>
                        @endif
                    @endif
                    @error('selectedBeerId') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Venue --}}
                <x-location-autocomplete
                    label="Venue"
                    prefix="venue"
                    model="App\\Models\\Venue"
                    :selectedId="$selectedVenueId"
                    :selectedName="$selectedVenueName"
                    :suggestions="$venueSuggestions"
                    :apiResults="$venueApiResults"
                    icon="map-pin"
                    placeholder="Venue name or place, e.g. Hop Lot Suttons Bay MI"
                />

                {{-- Rating & Serving --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                        <input
                            wire:model="rating"
                            type="number"
                            step="0.01"
                            min="0"
                            max="5"
                            id="rating"
                            placeholder="0 - 5 (optional)"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />
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
                </div>

                {{-- Date (edit mode only) --}}
                @if($checkinId)
                    <div>
                        <label for="checkin_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                        <input
                            wire:model="checkin_date"
                            type="datetime-local"
                            id="checkin_date"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 dark:[color-scheme:dark]"
                        />
                        @error('checkin_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea
                        wire:model="notes"
                        id="notes"
                        rows="3"
                        placeholder="Tasting notes..."
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                    ></textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Existing Photos (edit mode) --}}
                @if(count($existingPhotos) > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Photos</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($existingPhotos as $photo)
                                <div class="relative group">
                                    <img src="{{ str_starts_with($photo['path'], 'http') ? $photo['path'] : Storage::url($photo['path']) }}" alt="Checkin photo" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                                    <button
                                        type="button"
                                        wire:click="removeExistingPhoto({{ $photo['id'] }})"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Photos --}}
                @if(!$checkinId && $this->selectedBeer?->photo_path)
                    <div class="space-y-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                wire:model.live="useBeerPhoto"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Use beer photo</span>
                        </label>
                        @if($useBeerPhoto)
                            <div class="flex items-center gap-2">
                                <img src="{{ $this->selectedBeer->photo_url }}" alt="" class="w-16 h-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600" />
                                <span class="text-xs text-gray-500 dark:text-gray-400">Photo from {{ $selectedBeerName }}</span>
                            </div>
                        @else
                            <x-photo-upload
                                wireModel="photos"
                                :multiple="true"
                                label="Photos"
                                hint="Up to 10MB per photo. Multiple photos allowed."
                                error="photos.*"
                                :previews="$photos"
                                removeAction="removePhoto"
                            />
                        @endif
                    </div>
                @else
                    <x-photo-upload
                        wireModel="photos"
                        :multiple="true"
                        :label="$checkinId ? 'Add Photos' : 'Photos'"
                        hint="Up to 10MB per photo. Multiple photos allowed."
                        error="photos.*"
                        :previews="$photos"
                        removeAction="removePhoto"
                    />
                @endif
            </div>

            @if(!$checkinId && count($shareTargets) > 0)
                <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Share to:</span>
                    @foreach($shareTargets as $i => $target)
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input
                                wire:model="shareTargets.{{ $i }}.enabled"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                            />
                            <x-icon name="{{ $target['icon'] }}" size="3.5" :solid="true" class="text-amber-400" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $target['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($checkinId && !config('app.demo_mode'))
                        <button
                            type="button"
                            wire:click="deleteCheckin"
                            wire:confirm="Delete this check-in? This cannot be undone."
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                        >
                            <x-icon name="trash" size="4" />
                            Delete
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('checkins.index') }}"
                        wire:navigate
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >Cancel</a>
                    <x-primary-button size="lg" wire:loading.attr="disabled">
                        <svg wire:loading wire:target="submitCheckin" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        {{ $checkinId ? 'Save Changes' : 'Check In' }}
                    </x-primary-button>
                </div>
            </div>
        </form>
    </div>
</div>
