@props([
    'href',
    'label' => 'Back',
])

<div class="mb-4">
    <a href="{{ $href }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 rounded-sm transition-colors">
        <x-icon name="arrow-left" size="4" />
        {{ $label }}
    </a>
</div>
