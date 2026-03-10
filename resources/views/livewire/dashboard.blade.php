<div>
    {{-- Stats --}}
    <div class="flex gap-4 mb-8">
        <a href="{{ route('checkins.index') }}" wire:navigate class="flex-1 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md hover:scale-105 transition-all duration-200">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['total_checkins'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Check-ins</p>
        </a>
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['library_count'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">In Library</p>
        </div>
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['in_fridge'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">In Stock</p>
        </div>
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['avg_rating'] ? number_format($stats['avg_rating'], 2) . ' ★' : '—' }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Avg Rating</p>
        </div>
    </div>

    {{-- Recently Added --}}
    <x-beer-row title="Recently Added" :beers="$recentBeers" />

    {{-- Recently Checked In --}}
    <x-beer-row title="Recently Checked In" :beers="$recentCheckins" />

    {{-- Favorites --}}
    <x-beer-row title="Favorites" :beers="$favorites" />

    {{-- Collections --}}
    @if($collections->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Your Collections</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <div class="aspect-[3/4] bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                        @if($collection->cover_path)
                            <img src="{{ Storage::url($collection->cover_path) }}" alt="{{ $collection->name }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-1.243 1.007-2.25 2.25-2.25h13.5"/></svg>
                        @endif
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $collection->name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $collection->beers_count }} {{ Str::plural('beer', $collection->beers_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if($recentBeers->isEmpty() && $favorites->isEmpty())
    <div class="text-center py-16">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Your beer library is empty</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">Start by adding some beers to your collection.</p>
        <a href="{{ route('beers.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors">
            Add Your First Beer
        </a>
    </div>
    @endif
</div>
