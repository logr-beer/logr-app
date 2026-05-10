<a href="{{ route('collections.show', $collection) }}" wire:navigate class="group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
    <div class="aspect-[4/3] bg-gradient-to-br {{ $collection->is_dynamic ? 'from-purple-400 to-purple-600' : 'from-amber-400 to-amber-600' }} flex items-center justify-center relative">
        @if($collection->cover_path)
            <img src="{{ Storage::url($collection->cover_path) }}" alt="{{ $collection->name }}" class="w-full h-full object-cover">
        @else
            <x-icon name="collection" size="12" class="text-white/80" />
        @endif
        @if($collection->is_dynamic)
            <span class="absolute top-1.5 right-1.5 bg-black/50 text-white text-[9px] font-medium px-1.5 py-0.5 rounded-full">Dynamic</span>
        @endif
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $collection->name }}</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $collection->resolved_count }} {{ Str::plural('beer', $collection->resolved_count) }}</p>
    </div>
</a>
