<div>
    {{-- Header --}}
    <div class="mb-6">
        <x-page-header title="Your Check-ins" actionLabel="New" :actionHref="route('checkins.create')" />
        <div class="flex items-center gap-2 sm:justify-end">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search beers or breweries..." class="flex-1 sm:flex-none sm:w-56" />
            <x-sort-control :options="['newest' => 'Newest', 'rating' => 'Rating']" />
        </div>
    </div>

    @if($checkins->isEmpty())
        <x-empty-state
            :card="true"
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
                    <x-icon name="trash" size="4" /> Delete
                </button>
            @endunless
        </x-floating-action-bar>
    @endif
</div>
