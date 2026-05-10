@props([
    'placeholder' => 'Search...',
])

<div class="relative {{ $attributes->get('class', 'w-full sm:w-56') }}">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
    <input
        {{ $attributes->except('class')->merge(['type' => 'text']) }}
        placeholder="{{ $placeholder }}"
        class="w-full pl-9 pr-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-amber-500 focus:border-amber-500"
    />
</div>
