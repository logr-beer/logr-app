@props(['tabs', 'active', 'wireModel' => null])

<div class="inline-flex items-stretch">
    @foreach($tabs as $key => $tab)
        @php
            $isActive = $active === $key;
            $label = is_string($tab) ? $tab : $tab['label'];
            $href = is_array($tab) ? ($tab['href'] ?? null) : null;
            $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
            $classes = $isActive
                ? 'bg-amber-600 text-white border-amber-600 dark:border-amber-600'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700';
            $rounded = $loop->first && $loop->last ? 'rounded-lg'
                : ($loop->first ? 'rounded-l-lg' : ($loop->last ? 'rounded-r-lg' : ''));
            $border = $loop->first ? 'border' : 'border border-l-0';
        @endphp

        @if($href)
            <a href="{{ $href }}" wire:navigate class="relative px-3 py-1.5 text-sm font-medium transition-colors {{ $border }} {{ $rounded }} {{ $classes }} focus:outline-none focus:ring-2 focus:ring-amber-500 focus:z-10 {{ $rounded ?: 'focus:rounded-sm' }}">
                {{ $label }}
                @if($badge)
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold rounded-full {{ $isActive ? 'bg-white/30' : 'bg-amber-600 text-white' }}">{{ $badge }}</span>
                @endif
            </a>
        @else
            <button wire:click="{{ $wireModel ? "\$set('{$wireModel}', '{$key}')" : '' }}" class="relative px-3 py-1.5 text-sm font-medium transition-colors {{ $border }} {{ $rounded }} {{ $classes }} focus:outline-none focus:ring-2 focus:ring-amber-500 focus:z-10 {{ $rounded ?: 'focus:rounded-sm' }}">
                {{ $label }}
                @if($badge)
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold rounded-full {{ $isActive ? 'bg-white/30' : 'bg-amber-600 text-white' }}">{{ $badge }}</span>
                @endif
            </button>
        @endif
    @endforeach
</div>
