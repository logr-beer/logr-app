<a href="{{ route('beers.show', $item->beer) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0 w-10 h-14 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden">
            @if($item->beer->photo_path)
                <img src="{{ $item->beer->photo_url }}" alt="{{ $item->beer->name }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <x-application-logo-filled class="w-8 h-8 stroke-current" />
                </div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item->beer->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                </div>
                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs font-medium flex-shrink-0">&times; {{ $item->quantity }}</span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                @if($item->storage_location)
                    <span>{{ $item->storage_location }}</span>
                @endif
                @if($item->store)
                    <span>{{ $item->store->name }}</span>
                @endif
                @if($item->date_acquired)
                    <span>{{ $item->date_acquired->format('M j, Y') }}</span>
                @endif
                @if($item->is_gift)
                    <span class="inline-flex items-center px-1.5 py-0.5 bg-pink-100 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 rounded text-[10px] font-medium">Gift</span>
                @endif
            </div>
        </div>
    </div>
</a>
