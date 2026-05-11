@props([
    'placeholder' => 'Search...',
])

<div class="relative {{ $attributes->get('class', 'w-full sm:w-56') }}">
    <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
    <input
        {{ $attributes->except('class')->merge(['type' => 'text']) }}
        placeholder="{{ $placeholder }}"
        class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
    />
</div>
