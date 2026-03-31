<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Your Check-ins</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-center gap-2 ml-auto flex-shrink-0">
                <button
                    wire:click="toggleSelecting"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $selecting ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    {{ $selecting ? 'Cancel' : 'Select' }}
                </button>
            </div>
        </div>
    </div>

    @if($checkins->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No check-ins yet</h3>
            <p class="text-gray-500 dark:text-gray-400">Check in to a beer to start tracking your tastings.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($checkins as $checkin)
                @if($selecting)
                    <div
                        wire:click="toggleSelected({{ $checkin->id }})"
                        class="cursor-pointer group relative rounded-lg bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-[1.025] transition-all duration-200 {{ in_array($checkin->id, $selected) ? 'ring-2 ring-amber-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}"
                    >
                        {{-- Selection circle --}}
                        <div class="absolute top-2 left-2 z-20">
                            @if(in_array($checkin->id, $selected))
                                <div class="w-7 h-7 rounded-full bg-amber-500 flex items-center justify-center shadow-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </div>
                            @else
                                <div class="w-7 h-7 rounded-full border-[3px] border-white dark:border-gray-300 bg-black/20 dark:bg-white/20 shadow-lg backdrop-blur-sm"></div>
                            @endif
                        </div>

                        {{-- Rating badge --}}
                        @if($checkin->rating !== null)
                            <div class="absolute top-2 right-2 z-20 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded-full">
                                {{ number_format($checkin->rating, 1) }} ★
                            </div>
                        @endif

                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden relative">
                            @if($checkin->beer->photo_path)
                                <img src="{{ Storage::url($checkin->beer->photo_path) }}" alt="{{ $checkin->beer->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <x-application-logo class="w-12 h-12 stroke-current" />
                                </div>
                            @endif

                            {{-- Serving Type Badge --}}
                            @if($checkin->serving_type)
                                <div class="absolute bottom-1.5 left-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-300 backdrop-blur-sm">
                                        {{ ucfirst($checkin->serving_type) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $checkin->beer->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $checkin->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                @if($checkin->beer->style)
                                    <span class="text-xs text-amber-600 dark:text-amber-400 truncate">{{ implode(', ', $checkin->beer->style) }}</span>
                                @endif
                                @if($checkin->beer->abv)
                                    <span class="text-xs text-gray-400">{{ $checkin->beer->abv }}%</span>
                                @endif
                            </div>
                            <time class="block mt-1 text-[10px] text-gray-400 dark:text-gray-500" datetime="{{ $checkin->created_at->toISOString() }}">
                                Checked in {{ $checkin->created_at->diffForHumans() }}
                            </time>
                        </div>
                    </div>
                @else
                    <x-beer-card
                        :beer="$checkin->beer"
                        :href="route('checkins.edit', $checkin)"
                        :date="$checkin->created_at"
                        dateLabel="Checked in"
                        :showFavorite="false"
                        :servingType="$checkin->serving_type"
                    />
                @endif
            @endforeach
        </div>

        <div class="mt-8 {{ $selecting ? 'mb-24' : '' }}">
            {{ $checkins->links() }}
        </div>
    @endif

    {{-- Floating Action Bar --}}
    @if($selecting)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 dark:bg-gray-700 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center gap-3 max-w-[95vw]">
            <span class="text-sm font-medium whitespace-nowrap">{{ count($selected) }} selected</span>

            <div class="w-px h-6 bg-gray-600"></div>

            <button wire:click="selectAll" class="text-sm text-amber-400 hover:text-amber-300 whitespace-nowrap">All</button>
            @if(count($selected) > 0)
                <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-gray-200 whitespace-nowrap">None</button>
            @endif

            @if(count($selected) > 0)
                <div class="w-px h-6 bg-gray-600"></div>

                {{-- Delete --}}
                <button
                    wire:click="deleteSelected"
                    wire:confirm="Delete {{ count($selected) }} check-in(s)? This cannot be undone."
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete
                </button>
            @endif

            <button wire:click="toggleSelecting" class="text-sm text-gray-400 hover:text-white transition-colors ml-1">Cancel</button>
        </div>
    @endif
</div>
