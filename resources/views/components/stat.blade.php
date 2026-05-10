@props([
    'value',
    'label',
    'href' => null,
])

@php
$tag = $href ? 'a' : 'div';
$linkAttrs = $href ? "href=\"{$href}\" wire:navigate" : '';
@endphp

<{{ $tag }} {!! $linkAttrs !!} class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm {{ $href ? 'hover:shadow-md hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900' : '' }}">
    <p class="text-2xl font-bold text-amber-500">{{ $value }}</p>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
</{{ $tag }}>
