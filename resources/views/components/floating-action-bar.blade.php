@props([
    'count' => 0,
])

<div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 dark:bg-gray-700 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center gap-3 max-w-[95vw]">
    <span class="text-sm font-medium whitespace-nowrap">{{ $count }} selected</span>

    <div class="w-px h-6 bg-gray-600"></div>

    <button wire:click="selectAll" class="text-sm text-amber-400 hover:text-amber-300 whitespace-nowrap">All</button>
    <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-gray-200 whitespace-nowrap">None</button>

    <div class="w-px h-6 bg-gray-600"></div>

    {{ $slot }}

    <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-white transition-colors ml-1">Cancel</button>
</div>
