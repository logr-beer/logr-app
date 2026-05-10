@props([
    'href',
    'label' => 'Back',
])

<div class="mb-4">
    <a href="{{ $href }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors">
        <x-icon name="arrow-left" size="4" />
        {{ $label }}
    </a>
</div>
