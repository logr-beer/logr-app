<div>
    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('checkins.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to Check-ins
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 max-w-2xl mx-auto">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
            @if($checkinId)
                <svg class="w-5 h-5 inline-block mr-1 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                Edit Check-in
            @else
                <svg class="w-5 h-5 inline-block mr-1 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
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
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedBeerName }}</span>
                            <button type="button" wire:click="clearBeer" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082"/></svg>
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
                            placeholder="0 - 5 (optional)"
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
                </div>

                {{-- Date (shown in edit mode or can be toggled) --}}
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

                {{-- Venue --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Venue</label>

                    @if($selectedVenueId)
                        <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-400 flex-1">{{ $selectedVenueName }}</span>
                            <button type="button" wire:click="clearVenue" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
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
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        <svg wire:loading wire:target="submitCheckin" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        {{ $checkinId ? 'Save Changes' : 'Check In' }}
                    </button>

                    @if($checkinId)
                        <button
                            type="button"
                            wire:click="deleteCheckin"
                            wire:confirm="Delete this check-in? This cannot be undone."
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            Delete
                        </button>
                    @endif
                </div>

                @if(!$checkinId && (!empty(auth()->user()->getData('discord_webhooks')) || !empty(auth()->user()->getData('discord_bots'))))
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
</div>
