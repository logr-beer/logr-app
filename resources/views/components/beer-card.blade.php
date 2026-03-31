@props(['beer', 'date' => null, 'dateLabel' => null, 'href' => null, 'showFavorite' => true, 'servingType' => null])

@php
    $link = $href ?? route('beers.show', $beer);
    $avgRating = $beer->averageRating();
    $displayDate = $date ?? $beer->created_at;
    $displayDateLabel = $dateLabel ?? 'Added';
@endphp

<div class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-[1.025] transition-all duration-150 hover:duration-[250ms]">
    <a href="{{ $link }}" wire:navigate>
        <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
            @if($beer->photo_path)
                <img src="{{ Storage::url($beer->photo_path) }}" alt="{{ $beer->name }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                    <x-application-logo class="w-12 h-12 stroke-current" />
                </div>
            @endif

            {{-- Serving Type Badge --}}
            @if($servingType)
                <div class="absolute bottom-1.5 left-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-300 backdrop-blur-sm">
                        {{ ucfirst($servingType) }}
                    </span>
                </div>
            @endif
        </div>
    </a>

    {{-- Favorite toggle --}}
    @if($showFavorite)
        <div class="absolute top-2 right-2">
            <button
                wire:click="toggleFavorite({{ $beer->id }})"
                class="group/fav p-1.5 rounded-full {{ $beer->is_favorite ? 'bg-black/50' : 'bg-black/50 opacity-0 group-hover:opacity-100' }} text-white transition-all"
            >
                @if($beer->is_favorite)
                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path class="transition-[fill] duration-150 group-hover/fav:fill-red-400 group-hover/fav:duration-[250ms]" stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                @endif
            </button>
        </div>
    @endif

    {{-- Rating badge --}}
    @if($avgRating > 0)
        <div class="absolute top-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded-full">
            {{ number_format($avgRating, 1) }} ★
        </div>
    @endif

    {{-- Info --}}
    <div class="p-3">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $beer->name }}</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $beer->brewery?->name ?? 'Unknown Brewery' }}</p>
        <div class="flex items-center gap-2 mt-1">
            @if($beer->style)
                <span class="text-xs text-amber-600 dark:text-amber-400 truncate">{{ implode(', ', $beer->style) }}</span>
            @endif
            @if($beer->abv)
                <span class="text-xs text-gray-400">{{ $beer->abv }}%</span>
            @endif
        </div>
        @if($displayDate)
            <time class="block mt-1 text-[10px] text-gray-400 dark:text-gray-500" datetime="{{ $displayDate->toISOString() }}">
                {{ $displayDateLabel }} {{ $displayDate->diffForHumans() }}
            </time>
        @endif
    </div>
</div>
