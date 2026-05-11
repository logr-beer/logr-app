@props(['beer', 'date' => null, 'dateLabel' => null, 'href' => null, 'showFavorite' => true, 'servingType' => null, 'selectable' => false, 'selected' => false, 'selectId' => null, 'badges' => null, 'subtitle' => null])

@php
    $link = $href ?? route('beers.show', $beer);
    $avgRating = $beer->averageRating();
    $displayDate = $date ?? $beer->created_at;
    $displayDateLabel = $dateLabel ?? 'Added';
    $itemId = $selectId ?? $beer->id;

    // Default badges if none provided
    if ($badges === null) {
        $badges = [];
        if ($beer->abv) {
            $badges[] = ['label' => $beer->abv . '%', 'position' => 'left', 'style' => 'dark', 'icon' => 'flask'];
        }
        if ($avgRating > 0) {
            $badges[] = ['label' => number_format($avgRating, 1) . ' ★', 'position' => 'right', 'style' => 'dark'];
        }
    }

    $leftBadges = collect($badges)->where('position', 'left')->values();
    $rightBadges = collect($badges)->where('position', 'right')->values();
@endphp

<div
    x-data="{ heartAnim: '' }"
    @if($showFavorite) x-on:favorite-toggled-{{ $beer->id }}.window="heartAnim = $event.detail?.action || 'favorite'; setTimeout(() => heartAnim = '', 600)" @endif
    class="group relative flex flex-col rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-[1.025] transition-all duration-150 hover:duration-[250ms] {{ $selected ? 'ring-2 ring-amber-500 ring-offset-2 dark:ring-offset-gray-900' : '' }} focus-within:ring-2 focus-within:ring-amber-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-gray-900"
>
    <a href="{{ $link }}" wire:navigate class="focus:outline-none" @if($showFavorite) @keydown.f.prevent="$dispatch('favorite-toggled-{{ $beer->id }}', { action: {{ $beer->is_favorite ? '\'unfavorite\'' : '\'favorite\'' }} }); $wire.toggleFavorite({{ $beer->id }})" @endif @if($selectable) @keydown.s.prevent="$wire.toggleSelected({{ $itemId }})" @endif>
        <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
            @if($beer->photo_path)
                <img src="{{ Storage::url($beer->photo_path) }}" alt="{{ $beer->name }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <x-application-logo-filled class="w-16 h-16 stroke-current" />
                </div>
            @endif

            {{-- Bottom badges row --}}
            @if($leftBadges->isNotEmpty() || $rightBadges->isNotEmpty())
                <div class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center gap-1">
                    @foreach($leftBadges as $badge)
                        @php
                            $badgeClasses = match($badge['style'] ?? 'dark') {
                                'light' => 'bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-300 backdrop-blur-sm font-medium',
                                'pink' => 'bg-pink-500/80 text-white font-bold',
                                default => 'bg-black/70 text-white font-bold',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs {{ $badgeClasses }}">
                            @if(($badge['icon'] ?? null) === 'flask')
                                <x-icon name="flask" size="2.5" />
                            @elseif(($badge['icon'] ?? null) === 'glass')
                                <x-icon name="glass" size="2.5" />
                            @endif
                            {{ $badge['label'] }}
                        </span>
                    @endforeach

                    <div class="flex-1"></div>

                    @foreach($rightBadges as $badge)
                        @php
                            $badgeClasses = match($badge['style'] ?? 'dark') {
                                'light' => 'bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-300 backdrop-blur-sm font-medium',
                                'pink' => 'bg-pink-500/80 text-white font-bold',
                                default => 'bg-black/70 text-white font-bold',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs {{ $badgeClasses }}">
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
            {{-- Heart animation overlays --}}
            @if($showFavorite)
                <style>
                    @keyframes heart-expand {
                        0% { opacity: 1; transform: scale(0.3); }
                        100% { opacity: 0; transform: scale(2.5); }
                    }
                    @keyframes heart-shrink {
                        0% { opacity: 0.7; transform: scale(2.5); }
                        100% { opacity: 0; transform: scale(0.3); }
                    }
                </style>
                <div
                    x-show="heartAnim !== ''"
                    :class="heartAnim === 'favorite' ? 'animate-[heart-expand_500ms_ease-out_forwards]' : 'animate-[heart-shrink_500ms_ease-in_forwards]'"
                    class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
                    x-cloak
                >
                    <svg class="w-3/4 h-3/4 text-amber-500/70 drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
                    </svg>
                </div>
            @endif
        </div>
    </a>

    {{-- Select circle (top-left, visible on hover or when selected) --}}
    @if($selectable)
        <button
            wire:click.prevent.stop="toggleSelected({{ $itemId }})"
            tabindex="-1"
            class="absolute top-2 left-2 z-20 {{ $selected ? '' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"
        >
            @if($selected)
                <div class="w-5 h-5 rounded-full bg-amber-600 flex items-center justify-center shadow-lg">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>
            @else
                <div class="w-5 h-5 rounded-full border-2 border-white dark:border-gray-300 bg-black/20 dark:bg-white/20 shadow-lg backdrop-blur-sm"></div>
            @endif
        </button>
    @endif

    {{-- Top-right: Favorite --}}
    @if($showFavorite)
        <button
            wire:click.prevent.stop="toggleFavorite({{ $beer->id }})"
            x-on:click="$dispatch('favorite-toggled-{{ $beer->id }}', { action: '{{ $beer->is_favorite ? 'unfavorite' : 'favorite' }}' })"
            tabindex="-1"
            class="absolute top-1.5 right-1.5 z-10 group/fav w-6 h-6 flex items-center justify-center rounded-full {{ $beer->is_favorite ? 'bg-black/50' : 'bg-black/50 opacity-0 group-hover:opacity-100' }} text-white shadow-lg transition-all"
        >
            <x-icon name="heart" size="3.5" :solid="$beer->is_favorite" class="{{ $beer->is_favorite ? 'text-amber-400' : 'transition-[fill] duration-150 group-hover/fav:fill-amber-400 group-hover/fav:duration-[250ms]' }}" />
        </button>
        {{-- Keyboard hint (visible on focus, hidden if already favorited) --}}
        @unless($beer->is_favorite)
            <span class="absolute top-1.5 right-1.5 z-10 hidden group-focus-within:flex w-6 h-6 items-center justify-center rounded-full bg-black/60 text-white text-xs font-bold shadow-lg pointer-events-none group-hover:hidden" aria-hidden="true">F</span>
        @endunless
    @endif

    {{-- Select keyboard hint --}}
    @if($selectable && !$selected)
        <span class="absolute top-2 left-2 z-10 hidden group-focus-within:flex w-5 h-5 items-center justify-center rounded-full bg-black/60 text-white text-[10px] font-bold shadow-lg pointer-events-none group-hover:hidden" aria-hidden="true">S</span>
    @endif

    {{-- Info --}}
    <div class="p-3 flex flex-col flex-1">
        <h3 class="font-semibold text-base text-gray-900 dark:text-white line-clamp-2">{{ $beer->name }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ $beer->brewery?->name ?? 'Unknown Brewery' }}</p>
        @if($subtitle)
            <p class="text-sm text-amber-600 dark:text-amber-400 line-clamp-1 mt-1">{{ $subtitle }}</p>
        @elseif($beer->style)
            <p class="text-sm text-amber-600 dark:text-amber-400 line-clamp-1 mt-1">{{ implode(', ', $beer->style) }}</p>
        @endif
        @if($displayDate)
            <time class="block mt-auto pt-1 text-xs text-gray-500 dark:text-gray-400" datetime="{{ $displayDate->toISOString() }}">
                {{ $displayDateLabel }} {{ $displayDate->diffForHumans() }}
            </time>
        @endif
    </div>
</div>
