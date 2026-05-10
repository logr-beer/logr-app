@props(['tabs', 'active', 'wireModel' => null])

<div class="inline-flex items-stretch rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
    @foreach($tabs as $key => $tab)
        @php
            $isActive = $active === $key;
            $label = is_string($tab) ? $tab : $tab['label'];
            $href = is_array($tab) ? ($tab['href'] ?? null) : null;
            $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
            $classes = $isActive
                ? 'bg-amber-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
        @endphp

        @if(!$loop->first)
            <div class="w-px bg-gray-300 dark:bg-gray-600"></div>
        @endif

        @if($href)
            <a href="{{ $href }}" wire:navigate class="px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset {{ $classes }}">
                {{ $label }}
                @if($badge)
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold rounded-full {{ $isActive ? 'bg-white/30' : 'bg-amber-600 text-white' }}">{{ $badge }}</span>
                @endif
            </a>
        @else
            <button wire:click="{{ $wireModel ? "\$set('{$wireModel}', '{$key}')" : '' }}" class="px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset {{ $classes }}">
                {{ $label }}
                @if($badge)
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold rounded-full {{ $isActive ? 'bg-white/30' : 'bg-amber-600 text-white' }}">{{ $badge }}</span>
                @endif
            </button>
        @endif
    @endforeach
</div>
