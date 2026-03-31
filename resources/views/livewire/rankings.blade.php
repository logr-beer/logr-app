<div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Rankings</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Rated --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                    Top Rated
                    <span class="text-xs font-normal text-gray-400">(min 2 check-ins)</span>
                </h2>
                @if($topRated->isNotEmpty())
                    <div class="flex items-center gap-1">
                        <button wire:click="expandSection('topRated', 5)" class="px-2 py-1 rounded text-xs font-medium {{ $topRatedLimit === 5 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">5</button>
                        <button wire:click="expandSection('topRated', 10)" class="px-2 py-1 rounded text-xs font-medium {{ $topRatedLimit === 10 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">10</button>
                        <button wire:click="expandSection('topRated', 25)" class="px-2 py-1 rounded text-xs font-medium {{ $topRatedLimit === 25 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">25</button>
                    </div>
                @endif
            </div>
            @if($topRated->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">Not enough check-ins with ratings yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($topRated as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $beer->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beer->brewery?->name ?? 'Unknown' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-yellow-500">{{ number_format($beer->avg_rating, 2) }} ★</p>
                                <p class="text-xs text-gray-400">{{ $beer->checkin_count }} {{ Str::plural('check-in', $beer->checkin_count) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Most Checked In --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    Most Checked In
                </h2>
                @if($mostCheckedIn->isNotEmpty())
                    <div class="flex items-center gap-1">
                        <button wire:click="expandSection('mostCheckedIn', 5)" class="px-2 py-1 rounded text-xs font-medium {{ $mostCheckedInLimit === 5 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">5</button>
                        <button wire:click="expandSection('mostCheckedIn', 10)" class="px-2 py-1 rounded text-xs font-medium {{ $mostCheckedInLimit === 10 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">10</button>
                        <button wire:click="expandSection('mostCheckedIn', 25)" class="px-2 py-1 rounded text-xs font-medium {{ $mostCheckedInLimit === 25 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">25</button>
                    </div>
                @endif
            </div>
            @if($mostCheckedIn->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No check-ins yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($mostCheckedIn as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $beer->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beer->brewery?->name ?? 'Unknown' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-amber-500">{{ $beer->checkin_count }} ×</p>
                                @if($beer->avg_rating)
                                    <p class="text-xs text-gray-400">{{ number_format($beer->avg_rating, 1) }} ★</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top Breweries --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
                    Top Breweries
                    <span class="text-xs font-normal text-gray-400">(min 3 check-ins)</span>
                </h2>
                @if($topBreweries->isNotEmpty())
                    <div class="flex items-center gap-1">
                        <button wire:click="expandSection('topBreweries', 5)" class="px-2 py-1 rounded text-xs font-medium {{ $topBreweriesLimit === 5 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">5</button>
                        <button wire:click="expandSection('topBreweries', 10)" class="px-2 py-1 rounded text-xs font-medium {{ $topBreweriesLimit === 10 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">10</button>
                        <button wire:click="expandSection('topBreweries', 25)" class="px-2 py-1 rounded text-xs font-medium {{ $topBreweriesLimit === 25 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">25</button>
                    </div>
                @endif
            </div>
            @if($topBreweries->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">Not enough check-ins yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($topBreweries as $i => $brewery)
                        <div class="flex items-center gap-3 p-3 rounded-lg">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $brewery->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $brewery->beer_count }} {{ Str::plural('beer', $brewery->beer_count) }} · {{ $brewery->checkin_count }} {{ Str::plural('check-in', $brewery->checkin_count) }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-yellow-500">{{ number_format($brewery->avg_rating, 2) }} ★</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Highest ABV --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/></svg>
                    Highest ABV
                </h2>
                @if($highestAbv->isNotEmpty())
                    <div class="flex items-center gap-1">
                        <button wire:click="expandSection('highestAbv', 5)" class="px-2 py-1 rounded text-xs font-medium {{ $highestAbvLimit === 5 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">5</button>
                        <button wire:click="expandSection('highestAbv', 10)" class="px-2 py-1 rounded text-xs font-medium {{ $highestAbvLimit === 10 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">10</button>
                        <button wire:click="expandSection('highestAbv', 25)" class="px-2 py-1 rounded text-xs font-medium {{ $highestAbvLimit === 25 ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">25</button>
                    </div>
                @endif
            </div>
            @if($highestAbv->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No beers with ABV data yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($highestAbv as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $beer->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beer->brewery?->name ?? 'Unknown' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-amber-500">{{ $beer->abv }}%</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
