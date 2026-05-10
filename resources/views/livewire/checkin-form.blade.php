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
                        <input
                            wire:model.live.debounce.300ms="beerQuery"
                            @focus="open = true"
                            @input="open = true"
                            type="text"
                            placeholder="Search for a beer..."
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />

                        @if(count($beerSuggestions) > 0)
                            <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($beerSuggestions as $beer)
                                    <button
                                        type="button"
                                        wire:click="selectBeer({{ $beer->id }})"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-3"
                                    >
                                        @if($beer->photo_path)
                                            <img src="{{ Storage::url($beer->photo_path) }}" alt="" class="w-8 h-8 rounded object-cover flex-shrink-0" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                <x-application-logo-filled class="w-6 h-6 stroke-current text-gray-400" />
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-gray-900 dark:text-white font-medium">{{ $beer->name }}</span>
                                            @if($beer->brewery)
                                                <span class="text-gray-400 dark:text-gray-500 text-xs block">{{ $beer->brewery->name }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if(strlen($beerQuery) >= 2 && count($beerSuggestions) === 0)
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">No beers found matching "{{ $beerQuery }}".</p>
                        @endif
                    @endif
                    @error('selectedBeerId') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Venue --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
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
                        <input
                            wire:model.live.debounce.300ms="venueQuery"
                            @focus="open = true"
                            @input="open = true"
                            type="text"
                            placeholder="Type a venue name..."
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                        />

                        @if(count($venueSuggestions) > 0)
                            <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($venueSuggestions as $venue)
                                    <button
                                        type="button"
                                        wire:click="selectVenue({{ $venue->id }})"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                                    >
                                        <x-icon name="map-pin" size="4" class="text-gray-400 flex-shrink-0" />
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
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
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
                                    <img src="{{ Storage::url($photo['path']) }}" alt="Checkin photo" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $checkinId ? 'Add Photos' : 'Photos' }}</label>
                    <input
                        wire:model="photos"
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
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Up to 10MB per photo. Multiple photos allowed.</p>
                    @error('photos.*') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

                    {{-- Photo previews --}}
                    @if($photos)
                        <div class="flex flex-wrap gap-3 mt-3">
                            @foreach($photos as $index => $photo)
                                <div class="relative group">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                                    <button
                                        type="button"
                                        wire:click="removePhoto({{ $index }})"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="photos" class="mt-2 text-sm text-amber-500">
                        Uploading photos...
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
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
                    @if(!$checkinId && (!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots'))))
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                wire:model="shareCheckinToDiscord"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700"
                            />
                            <x-icon name="discord" size="4" :solid="true" class="text-amber-400" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">Share to Discord</span>
                        </label>
                    @endif

                    <a
                        href="{{ route('checkins.index') }}"
                        wire:navigate
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >Cancel</a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        <svg wire:loading wire:target="submitCheckin" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        {{ $checkinId ? 'Save Changes' : 'Check In' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
