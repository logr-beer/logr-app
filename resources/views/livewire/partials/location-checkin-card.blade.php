<a href="{{ route('beers.show', $checkin->beer) }}" wire:navigate class="block bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0 w-10 h-14 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden">
            @if($checkin->beer->photo_path)
                <img src="{{ $checkin->beer->photo_url }}" alt="{{ $checkin->beer->name }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <x-application-logo-filled class="w-8 h-8 stroke-current" />
                </div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $checkin->beer->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $checkin->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                </div>
                <time class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $checkin->created_at->diffForHumans() }}</time>
            </div>
            <div class="flex items-center gap-2 mt-1">
                @if($checkin->rating !== null)
                    <div class="flex items-center gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <x-icon name="star" size="3" :solid="true" class="{{ $i <= $checkin->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" />
                        @endfor
                    </div>
                @endif
                @if($checkin->serving_type)
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($checkin->serving_type) }}</span>
                @endif
            </div>
            @if($checkin->notes)
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-1">{{ $checkin->notes }}</p>
            @endif
        </div>
    </div>
</a>
