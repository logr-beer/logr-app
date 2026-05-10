<div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Stats</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Rated --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Top Rated" subtitle="(min 1 check-in)">
                <x-slot:icon>
                    <x-icon name="star" size="5" :solid="true" class="text-yellow-500" />
                </x-slot:icon>
                <x-slot:actions>
                    @if($topRated->isNotEmpty())
                        <x-size-toggle section="topRated" :current="$topRatedLimit" />
                    @endif
                </x-slot:actions>
            </x-section-heading>
            <x-ranked-list :items="$topRated" empty="Not enough check-ins with ratings yet.">
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
            </x-ranked-list>
        </x-card>

        {{-- Most Checked In --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Most Checked In">
                <x-slot:icon>
                    <x-icon name="map-pin" size="5" class="text-amber-500" />
                </x-slot:icon>
                <x-slot:actions>
                    @if($mostCheckedIn->isNotEmpty())
                        <x-size-toggle section="mostCheckedIn" :current="$mostCheckedInLimit" />
                    @endif
                </x-slot:actions>
            </x-section-heading>
            <x-ranked-list :items="$mostCheckedIn" empty="No check-ins yet.">
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
            </x-ranked-list>
        </x-card>

        {{-- Top Breweries --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Top Breweries" subtitle="(min 3 check-ins)">
                <x-slot:icon>
                    <x-icon name="building" size="5" class="text-amber-500" />
                </x-slot:icon>
                <x-slot:actions>
                    @if($topBreweries->isNotEmpty())
                        <x-size-toggle section="topBreweries" :current="$topBreweriesLimit" />
                    @endif
                </x-slot:actions>
            </x-section-heading>
            <x-ranked-list :items="$topBreweries" empty="Not enough check-ins yet.">
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
            </x-ranked-list>
        </x-card>

        {{-- Highest ABV --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Highest ABV">
                <x-slot:icon>
                    <x-icon name="flask" size="5" class="text-amber-500" />
                </x-slot:icon>
                <x-slot:actions>
                    @if($highestAbv->isNotEmpty())
                        <x-size-toggle section="highestAbv" :current="$highestAbvLimit" />
                    @endif
                </x-slot:actions>
            </x-section-heading>
            <x-ranked-list :items="$highestAbv" empty="No beers with ABV data yet.">
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
            </x-ranked-list>
        </x-card>

        {{-- Style Breakdown --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Top Styles">
                <x-slot:icon>
                    <x-icon name="tag" size="5" class="text-amber-500" />
                </x-slot:icon>
            </x-section-heading>
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
        </x-card>

        {{-- Serving Type --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Serving Types">
                <x-slot:icon>
                    <x-icon name="glass" size="5" class="text-amber-500" />
                </x-slot:icon>
            </x-section-heading>
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
        </x-card>

        {{-- Rating Distribution --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Rating Distribution">
                <x-slot:icon>
                    <x-icon name="star" size="5" :solid="true" class="text-yellow-500" />
                </x-slot:icon>
            </x-section-heading>
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
        </x-card>

        {{-- Top Venues --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Top Venues">
                <x-slot:icon>
                    <x-icon name="map-pin" size="5" class="text-amber-500" />
                </x-slot:icon>
            </x-section-heading>
            <x-ranked-list :items="$topVenues" empty="No venue check-ins yet.">
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
            </x-ranked-list>
        </x-card>

        {{-- Monthly Activity --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Monthly Activity" subtitle="(last 12 months)">
                <x-slot:icon>
                    <x-icon name="calendar" size="5" class="text-amber-500" />
                </x-slot:icon>
            </x-section-heading>
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
        </x-card>

        {{-- Country Breakdown --}}
        <x-card padding="4 sm:p-6">
            <x-section-heading title="Countries">
                <x-slot:icon>
                    <x-icon name="globe" size="5" class="text-amber-500" />
                </x-slot:icon>
            </x-section-heading>
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
        </x-card>
    </div>
</div>
