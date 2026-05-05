<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Your Check-ins</h1>
        <div class="flex items-center gap-2 sm:justify-end">
            <div class="relative flex-1 sm:flex-none">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search beers or breweries..." class="w-full sm:w-56 pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500" />
            </div>
            <x-sort-control :options="['newest' => 'Newest', 'rating' => 'Rating']" />
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
                <x-beer-card
                    :beer="$checkin->beer"
                    :href="route('checkins.edit', $checkin)"
                    :date="$checkin->created_at"
                    dateLabel="Checked in"
                    :showFavorite="false"
                    :servingType="$checkin->serving_type"
                    :selectable="true"
                    :selected="in_array($checkin->id, $selected)"
                    :selectId="$checkin->id"
                />
            @endforeach
        </div>

        <div class="mt-8 {{ count($selected) > 0 ? 'mb-24' : '' }}">
            {{ $checkins->links() }}
        </div>
    @endif

    {{-- Floating Action Bar (visible when any cards are selected) --}}
    @if(count($selected) > 0)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 dark:bg-gray-700 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center gap-3 max-w-[95vw]">
            <span class="text-sm font-medium whitespace-nowrap">{{ count($selected) }} selected</span>

            <div class="w-px h-6 bg-gray-600"></div>

            <button wire:click="selectAll" class="text-sm text-amber-400 hover:text-amber-300 whitespace-nowrap">All</button>
            <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-gray-200 whitespace-nowrap">None</button>

            <div class="w-px h-6 bg-gray-600"></div>

            {{-- Delete --}}
            @unless(config('app.demo_mode'))
                <button
                    wire:click="deleteSelected"
                    wire:confirm="Delete {{ count($selected) }} check-in(s)? This cannot be undone."
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete
                </button>
            @endunless

            <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-white transition-colors ml-1">Cancel</button>
        </div>
    @endif
</div>
