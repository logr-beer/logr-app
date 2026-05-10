@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        @if(isset($icon))
            {{ $icon }}
        @endif
        {{ $title }}
        @if($subtitle)
            <span class="text-xs font-normal text-gray-400">{{ $subtitle }}</span>
        @endif
    </h2>
    @if(isset($actions))
        {{ $actions }}
    @endif
</div>
