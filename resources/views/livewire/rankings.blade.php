<div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Stats</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Rated --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                    Top Rated
                    <span class="text-xs font-normal text-gray-400">(min 1 check-in)</span>
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
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($topRated as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 py-2 px-1 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4">
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
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($mostCheckedIn as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 py-2 px-1 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4">
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
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($topBreweries as $i => $brewery)
                        <div class="flex items-center gap-3 py-2 px-1">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4">
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
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($highestAbv as $i => $beer)
                        <a href="{{ route('beers.show', $beer) }}" wire:navigate class="flex items-center gap-3 py-2 px-1 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
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
        {{-- Style Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                Top Styles
            </h2>
            @if($styleBreakdown->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No style data yet.</p>
            @else
                <div class="space-y-2">
                    @php $maxStyle = $styleBreakdown->max(); @endphp
                    @foreach($styleBreakdown as $style => $count)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-32 truncate flex-shrink-0">{{ $style }}</span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full flex items-center justify-end pr-2" style="width: {{ ($count / $maxStyle) * 100 }}%">
                                    <span class="text-[10px] font-bold text-white">{{ $count }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Serving Type --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                Serving Types
            </h2>
            @if($servingTypes->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No serving type data yet.</p>
            @else
                <div class="space-y-2">
                    @php $maxServing = $servingTypes->max('count'); @endphp
                    @foreach($servingTypes as $type)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-24 truncate flex-shrink-0">{{ ucfirst($type->serving_type) }}</span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full flex items-center justify-end pr-2" style="width: {{ ($type->count / $maxServing) * 100 }}%">
                                    <span class="text-[10px] font-bold text-white">{{ $type->count }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Rating Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                Rating Distribution
            </h2>
            @if($ratingDistribution->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No ratings yet.</p>
            @else
                <div class="space-y-2">
                    @php $maxRating = $ratingDistribution->max(); @endphp
                    @for($r = 5; $r >= 1; $r--)
                        @php $count = $ratingDistribution->get($r, 0); @endphp
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-12 flex-shrink-0">{{ $r }} ★</span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                                @if($count > 0)
                                    <div class="bg-yellow-500 h-full rounded-full flex items-center justify-end pr-2" style="width: {{ $maxRating > 0 ? ($count / $maxRating) * 100 : 0 }}%">
                                        <span class="text-[10px] font-bold text-white">{{ $count }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            @endif
        </div>

        {{-- Top Venues --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                Top Venues
            </h2>
            @if($topVenues->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No venue check-ins yet.</p>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($topVenues as $i => $venue)
                        <div class="flex items-center gap-3 py-2 px-1 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $venue->name }}</p>
                                @if($venue->displayLocation())
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $venue->displayLocation() }}</p>
                                @endif
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-amber-500">{{ $venue->checkin_count }} ×</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Monthly Activity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                Monthly Activity
                <span class="text-xs font-normal text-gray-400">(last 12 months)</span>
            </h2>
            @if($monthlyActivity->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No check-ins yet.</p>
            @else
                <div class="flex items-end gap-1 h-32">
                    @php $maxMonth = $monthlyActivity->max(); @endphp
                    @foreach($monthlyActivity as $month => $count)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full bg-amber-500 rounded-t" style="height: {{ $maxMonth > 0 ? ($count / $maxMonth) * 100 : 0 }}%"></div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Country Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                Countries
            </h2>
            @if($countryBreakdown->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500">No country data yet.</p>
            @else
                <div class="space-y-2">
                    @php $maxCountry = $countryBreakdown->max(); @endphp
                    @foreach($countryBreakdown as $country => $count)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-28 truncate flex-shrink-0">{{ $country }}</span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full flex items-center justify-end pr-2" style="width: {{ ($count / $maxCountry) * 100 }}%">
                                    <span class="text-[10px] font-bold text-white">{{ $count }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
