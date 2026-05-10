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
        if ($servingType) {
            $badges[] = ['label' => ucfirst($servingType), 'position' => 'left', 'style' => 'light'];
        }
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

<div class="group relative flex flex-col rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-[1.025] transition-all duration-150 hover:duration-[250ms] {{ $selected ? 'ring-2 ring-amber-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
    <a href="{{ $link }}" wire:navigate>
        <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
            @if($beer->photo_path)
                <img src="{{ Storage::url($beer->photo_path) }}" alt="{{ $beer->name }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
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
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] {{ $badgeClasses }}">
                            @if(($badge['icon'] ?? null) === 'flask')
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
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
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] {{ $badgeClasses }}">
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </a>

    {{-- Select circle (top-left, visible on hover or when selected) --}}
    @if($selectable)
        <button
            wire:click.prevent.stop="toggleSelected({{ $itemId }})"
            class="absolute top-2 left-2 z-20 {{ $selected ? '' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"
        >
            @if($selected)
                <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center shadow-lg">
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
            class="absolute top-2 right-2 z-10 group/fav w-5 h-5 flex items-center justify-center rounded-full {{ $beer->is_favorite ? 'bg-black/50' : 'bg-black/50 opacity-0 group-hover:opacity-100' }} text-white shadow-lg transition-all"
        >
            @if($beer->is_favorite)
                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
            @else
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path class="transition-[fill] duration-150 group-hover/fav:fill-amber-400 group-hover/fav:duration-[250ms]" stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            @endif
        </button>
    @endif

    {{-- Info --}}
    <div class="p-3 flex flex-col flex-1">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2">{{ $beer->name }}</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $beer->brewery?->name ?? 'Unknown Brewery' }}</p>
        @if($subtitle)
            <p class="text-xs text-amber-600 dark:text-amber-400 line-clamp-1 mt-1">{{ $subtitle }}</p>
        @elseif($beer->style)
            <p class="text-xs text-amber-600 dark:text-amber-400 line-clamp-1 mt-1">{{ implode(', ', $beer->style) }}</p>
        @endif
        @if($displayDate)
            <time class="block mt-auto pt-1 text-[10px] text-gray-400 dark:text-gray-500" datetime="{{ $displayDate->toISOString() }}">
                {{ $displayDateLabel }} {{ $displayDate->diffForHumans() }}
            </time>
        @endif
    </div>
</div>
