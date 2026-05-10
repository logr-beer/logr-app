@props([
    'items',
    'empty' => 'No data yet.',
])

@if($items->isEmpty())
    <p class="text-sm text-gray-400 dark:text-gray-500">{{ $empty }}</p>
@else
    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
        {{ $slot }}
    </div>
@endif
