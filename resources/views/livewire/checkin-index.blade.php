<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Your Check-ins</h1>
        <div class="flex items-center gap-2 sm:justify-end">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search beers or breweries..." class="flex-1 sm:flex-none sm:w-56" />
            <x-sort-control :options="['newest' => 'Newest', 'rating' => 'Rating']" />
        </div>
    </div>

    @if($checkins->isEmpty())
        <x-empty-state
            :card="true"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'
            title="No check-ins yet"
            message="Check in to a beer to start tracking your tastings."
        />
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($checkins as $checkin)
                @php
                    $checkinBadges = [];
                    if ($checkin->serving_type) {
                        $checkinBadges[] = ['label' => ucfirst($checkin->serving_type), 'position' => 'left', 'style' => 'dark'];
                    }
                    if ($checkin->rating) {
                        $checkinBadges[] = ['label' => number_format($checkin->rating, 1) . ' ★', 'position' => 'right', 'style' => 'dark'];
                    }
                @endphp
                <x-beer-card
                    :beer="$checkin->beer"
                    :href="route('checkins.edit', $checkin)"
                    :date="$checkin->created_at"
                    dateLabel="Checked in"
                    :showFavorite="false"
                    :badges="$checkinBadges"
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
        <x-floating-action-bar :count="count($selected)">
            @unless(config('app.demo_mode'))
                <button wire:click="deleteSelected" wire:confirm="Delete {{ count($selected) }} check-in(s)? This cannot be undone." class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete
                </button>
            @endunless
        </x-floating-action-bar>
    @endif
</div>
